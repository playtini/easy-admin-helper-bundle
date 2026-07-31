# Doc Path Traversal Containment — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop `DocItemController` from serving `.md` files outside the configured doc directory, by routing both doc controllers' untrusted filename input through one shared, canonicalize-then-contain resolver.

**Architecture:** A new stateless service `DocPathResolver` resolves a user-supplied relative path against a base directory with `realpath()` and returns it only when the canonical result is a regular file strictly inside that base. `DocItemController` treats a rejected path exactly like a missing document (its existing "Not found" branch — no new response shape). `DocDiagramController` drops its `preg_replace` strip in favour of the same resolver and returns 404 on rejection.

**Tech Stack:** PHP 8.5, Symfony 7.4/8.1 framework-bundle, PHPUnit 13, PHPStan level 5.

**Spec:** `.claude/superpowers/specs/2026-07-31-doc-path-traversal-design.md`

## Global Constraints

- Work directly on `main`. No feature branches, no pull requests, no git worktrees (`CLAUDE.md`).
- Namespace root is `Playtini\EasyAdminHelperBundle\` → `src/`; tests are `Playtini\EasyAdminHelperBundle\Tests\` → `tests/`.
- `src/` files use `declare(strict_types=1);`. Test files in this repo do **not** — match the existing files (`tests/Frontmatter/FrontmatterParserTest.php` has no `declare` and no `final`).
- Services in this bundle are registered **explicitly** in `config/services.yaml`; only `src/Controller/` is glob-registered. A new class outside `src/Controller/` is not autowirable until it is listed there.
- Test suite is unit-only: plain `PHPUnit\Framework\TestCase`, bootstrap is bare `vendor/autoload.php`. Do **not** add a test kernel, `symfony/browser-kit`, or any new dependency.
- PHPStan runs at level 5 over `src/` and `tests/`. Do not add entries to `phpstan-baseline.neon`.
- CI enforces a 50% coverage gate via `bin/coverage-check.php`.
- The route requirement `'name' => '.+'` on `DocItemController` must stay unchanged.
- Release tags are plain and incrementing, no `v` prefix. Current: `1.35`. This release: `1.36`.

---

### Task 1: `DocPathResolver`

**Files:**
- Create: `src/Doc/DocPathResolver.php`
- Test: `tests/Doc/DocPathResolverTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Playtini\EasyAdminHelperBundle\Doc\DocPathResolver::resolveFile(string $baseDir, string $relativePath): ?string` — returns the canonical absolute path when `$relativePath` names an existing regular file strictly inside `$baseDir`, otherwise `null`. Never throws. Tasks 2 and 3 depend on exactly this signature and on `null` (not an exception) being the rejection signal.

- [ ] **Step 1: Write the failing test**

Create `tests/Doc/DocPathResolverTest.php` with this exact content:

```php
<?php

namespace Playtini\EasyAdminHelperBundle\Tests\Doc;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Doc\DocPathResolver;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DocPathResolverTest extends TestCase
{
    private DocPathResolver $resolver;
    private string $root;
    private string $baseDir;

    protected function setUp(): void
    {
        $this->resolver = new DocPathResolver();

        $root = sys_get_temp_dir() . '/doc_path_resolver_' . uniqid();
        mkdir($root . '/base/ops', 0777, true);
        mkdir($root . '/base/db', 0777, true);
        mkdir($root . '/base/sub', 0777, true);
        mkdir($root . '/basedocs', 0777, true);
        mkdir($root . '/outside', 0777, true);
        file_put_contents($root . '/base/ops/known-issues.md', '# known issues');
        file_put_contents($root . '/base/db/schema.md', '# schema');
        file_put_contents($root . '/basedocs/leak.md', '# leak');
        file_put_contents($root . '/outside/secret.md', '# secret');
        symlink($root . '/outside', $root . '/base/escape');

        // realpath() because sys_get_temp_dir() is itself symlinked on macOS
        $this->root = (string)realpath($root);
        $this->baseDir = $this->root . '/base';
    }

    protected function tearDown(): void
    {
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($entries as $entry) {
            // symlinked dirs are not descended into (hasChildren() defaults to $allowLinks = false),
            // so the link itself is yielded as a leaf and must be unlinked, not rmdir'd
            if ($entry->isLink() || $entry->isFile()) {
                unlink($entry->getPathname());
            } else {
                rmdir($entry->getPathname());
            }
        }
        rmdir($this->root);
    }

    public function testResolvesNestedFile(): void
    {
        $this->assertSame(
            $this->baseDir . '/ops/known-issues.md',
            $this->resolver->resolveFile($this->baseDir, 'ops/known-issues.md'),
        );
    }

    public function testResolvesBenignParentSegmentThatStaysInside(): void
    {
        $this->assertSame(
            $this->baseDir . '/ops/known-issues.md',
            $this->resolver->resolveFile($this->baseDir, 'db/../ops/known-issues.md'),
        );
    }

    public function testRejectsTraversalOutOfBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, '../outside/secret.md'));
    }

    public function testRejectsDeepTraversalToAnExistingFile(): void
    {
        // climbs above the fixture root and back down: proves containment rejects it,
        // rather than the path merely failing to exist
        $deep = '../../' . basename($this->root) . '/outside/secret.md';

        $this->assertNull($this->resolver->resolveFile($this->baseDir, $deep));
    }

    public function testRejectsSiblingDirectoryWithSharedPrefix(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, '../basedocs/leak.md'));
    }

    public function testRejectsSymlinkPointingOutsideBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'escape/secret.md'));
    }

    public function testRejectsDirectory(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'sub'));
    }

    public function testRejectsMissingFile(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, 'nope.md'));
    }

    public function testRejectsEmptyRelativePath(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, ''));
    }

    public function testRejectsNullByteWithoutError(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->baseDir, "\0"));
        $this->assertNull($this->resolver->resolveFile($this->baseDir, "ops/known-issues.md\0"));
        $this->assertNull($this->resolver->resolveFile($this->baseDir . "\0", 'ops/known-issues.md'));
    }

    public function testRejectsMissingBaseDir(): void
    {
        $this->assertNull($this->resolver->resolveFile($this->root . '/nope', 'ops/known-issues.md'));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `./vendor/bin/phpunit tests/Doc/DocPathResolverTest.php`
Expected: FAIL — `Error: Class "Playtini\EasyAdminHelperBundle\Doc\DocPathResolver" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Doc/DocPathResolver.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Doc;

/**
 * Resolves a user-supplied relative path against a base directory,
 * returning it only when it stays inside that directory.
 */
final class DocPathResolver
{
    public function resolveFile(string $baseDir, string $relativePath): ?string
    {
        // filesystem functions throw ValueError on null bytes since PHP 8
        if (str_contains($baseDir, "\0") || str_contains($relativePath, "\0")) {
            return null;
        }

        $base = realpath($baseDir);
        if ($base === false) {
            return null;
        }

        // realpath() collapses ".." and resolves symlinks, so containment
        // is checked on the canonical path, never on the raw input
        $path = realpath($base . '/' . $relativePath);
        if ($path === false) {
            return null;
        }

        // trailing separator: a sibling directory named "<base>docs" must not
        // satisfy the prefix match, and neither must the base directory itself
        if (!str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }

        if (!is_file($path)) {
            return null;
        }

        return $path;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `./vendor/bin/phpunit tests/Doc/DocPathResolverTest.php`
Expected: PASS — 11 tests, 13 assertions, no errors and no risky/warning output.

- [ ] **Step 5: Run static analysis**

Run: `./vendor/bin/phpstan`
Expected: `[OK] No errors`. If it reports anything about the new files, fix the code — do not touch `phpstan-baseline.neon`.

- [ ] **Step 6: Commit**

```bash
git add src/Doc/DocPathResolver.php tests/Doc/DocPathResolverTest.php
git commit -m "feat(doc): add DocPathResolver that contains paths to a base directory

Canonicalizes with realpath() and requires the result to be a regular
file strictly inside the base directory, so \"..\" segments and symlinks
pointing outside it are rejected. Returns null rather than throwing, so
callers can treat rejection as \"not found\"."
```

---

### Task 2: Contain `DocItemController`

**Files:**
- Modify: `src/Controller/Doc/DocItemController.php:17-37` (constructor and the path construction in `__invoke()`)
- Modify: `config/services.yaml`

**Interfaces:**
- Consumes: `DocPathResolver::resolveFile(string $baseDir, string $relativePath): ?string` from Task 1.
- Produces: nothing new. The controller's route, its rendered template, and its response shape are unchanged.

There is no controller test in this task. The suite is unit-only and these controllers have never had tests; the behaviour being fixed is fully covered by Task 1's resolver tests. Verification here is the full suite plus static analysis.

- [ ] **Step 1: Inject the resolver**

In `src/Controller/Doc/DocItemController.php`, add the import next to the existing `use` statements:

```php
use Playtini\EasyAdminHelperBundle\Doc\DocPathResolver;
```

and add the constructor parameter **before** the `#[Autowire]`-attributed one:

```php
    public function __construct(
        private readonly EasyAdminContext $easyAdminContext,
        private readonly FrontmatterParser $frontmatterParser,
        private readonly DocPathResolver $docPathResolver,
        #[Autowire('%kernel.project_dir%/doc')]
        private readonly string $docDir,
    ) {
    }
```

- [ ] **Step 2: Replace the vulnerable concatenation**

Replace these lines in `__invoke()`:

```php
        $filename = $this->docDir . '/' . $name . '.md';
        if (is_file($filename)) {
```

with:

```php
        $filename = $this->docPathResolver->resolveFile($this->docDir, $name . '.md');
        if ($filename !== null) {
```

Leave the body of the `if`, the `$data['title'] ??= ...` line, the `#[Route]` attribute (including `requirements: ['name' => '.+']`) and the `render()` call exactly as they are.

- [ ] **Step 3: Register the service**

In `config/services.yaml`, add this entry after the `FrontmatterParser` block and before the blank line preceding `ReleaseSessionEarlyListener`:

```yaml
    Playtini\EasyAdminHelperBundle\Doc\DocPathResolver:
        autowire: true
```

Do not mark it `public` — it is injected, never fetched from the container.

- [ ] **Step 4: Run the full suite and static analysis**

Run: `./vendor/bin/phpunit && ./vendor/bin/phpstan`
Expected: both green. `tests/ClassLoadableTest.php` picks up `src/Doc/DocPathResolver.php` automatically, so a namespace or filename mistake surfaces here.

- [ ] **Step 5: Commit**

```bash
git add src/Controller/Doc/DocItemController.php config/services.yaml
git commit -m "fix(doc): contain DocItemController paths to the doc directory

/admin/doc/../CLAUDE served <project_dir>/CLAUDE.md: the route parameter
was concatenated into a filesystem path with no containment check, and
requirements: ['name' => '.+'] permits '/'. The percent-encoded form was
equivalent because UrlMatcher::match() rawurldecodes before routing, and
the traversal was not bounded to the project directory.

Rejected paths now fall through to the existing 'Not found' branch, so
legitimate requests are unaffected. Reported against 1.35 by a consuming
project."
```

---

### Task 3: Contain `DocDiagramController`

**Files:**
- Modify: `src/Controller/Doc/DocDiagramController.php:17-28` (constructor and `__invoke()`)

**Interfaces:**
- Consumes: `DocPathResolver::resolveFile(string $baseDir, string $relativePath): ?string` from Task 1; the service registration from Task 2.
- Produces: nothing new.

This controller is not currently exploitable — stripping every `/` makes traversal inexpressible. It changes anyway so the bundle has one guard for one input class, and so input is rejected rather than silently rewritten.

- [ ] **Step 1: Replace the sanitizer with the resolver**

In `src/Controller/Doc/DocDiagramController.php`, add the import:

```php
use Playtini\EasyAdminHelperBundle\Doc\DocPathResolver;
```

Change the constructor to:

```php
    public function __construct(
        private readonly DocPathResolver $docPathResolver,
        #[Autowire('%kernel.project_dir%/var/doc/db')]
        private readonly string $dbDocDir,
    ) {}
```

Replace the whole body of `__invoke()`:

```php
    public function __invoke(string $filename): Response
    {
        $path = $this->docPathResolver->resolveFile($this->dbDocDir, $filename);
        if ($path === null) {
            throw $this->createNotFoundException();
        }

        /** @noinspection UseControllerShortcuts */
        return new BinaryFileResponse($path, public: false);
    }
```

The `preg_replace('#(\.\.|/)#', '', $filename); // sanitize` line goes away entirely. Two behaviour changes are intended here: traversal-shaped input now 404s instead of being rewritten into a different servable filename, and a missing diagram now 404s instead of 500ing out of `BinaryFileResponse`'s `FileNotFoundException`.

- [ ] **Step 2: Run the full suite and static analysis**

Run: `./vendor/bin/phpunit && ./vendor/bin/phpstan`
Expected: both green.

- [ ] **Step 3: Commit**

```bash
git add src/Controller/Doc/DocDiagramController.php
git commit -m "refactor(doc): resolve diagram paths through DocPathResolver

Replaces the preg_replace strip with the shared containment check, so the
bundle has one guard for user-supplied doc filenames instead of two
styles. Paths outside var/doc/db and missing diagrams now return 404
instead of being silently rewritten or throwing FileNotFoundException."
```

---

### Task 4: Verify and release 1.36

**Files:** none modified.

**Interfaces:**
- Consumes: the committed state of Tasks 1–3.
- Produces: tag `1.36` on `origin`.

- [ ] **Step 1: Run the full verification set**

```bash
./vendor/bin/phpunit
./vendor/bin/phpstan
composer coverage-check
```

Expected: suite green, PHPStan `[OK] No errors`, coverage gate passes at ≥50%. Do not proceed to the tag on any failure — report the output instead.

- [ ] **Step 2: Confirm the working tree is clean and on `main`**

```bash
git status --short && git log --oneline -4
```

Expected: no output from `git status --short`; the last three commits are Tasks 1, 2 and 3.

- [ ] **Step 3: Tag and push**

`composer.json` carries no `version` field, so the tag is the whole version bump.

```bash
git tag 1.36
git push origin main
git push origin 1.36
```

- [ ] **Step 4: Confirm the release landed**

```bash
git ls-remote --tags origin | grep -E '1\.3[5-9]$'
```

Expected: `1.36` present on the remote.

---

## Self-Review

**Spec coverage:** Resolver component → Task 1. `DocItemController` change → Task 2. `DocDiagramController` change → Task 3. `config/services.yaml` wiring → Task 2 (folded into its first consumer). Testing section, all eleven cases → Task 1 Step 1. Verification section → Tasks 2–4. Release section → Task 4. Out-of-scope items (route requirement, configurable suffix, kernel tests, bzcrm changes) appear in no task, as intended.

**Placeholders:** none — every code step carries the literal content to write.

**Type consistency:** `resolveFile(string, string): ?string` is defined in Task 1 and called with that signature in Tasks 2 and 3; both consumers branch on `null`, matching the documented rejection signal.
