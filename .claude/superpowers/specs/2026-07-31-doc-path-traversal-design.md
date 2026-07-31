---
title: Doc path traversal — containment design
date: 2026-07-31
status: approved
---

# Design: contain doc filesystem paths in `DocItemController` and `DocDiagramController`

## Problem

`src/Controller/Doc/DocItemController.php:32` builds a filesystem path by
concatenating a user-controlled route parameter:

```php
$filename = $this->docDir . '/' . $name . '.md';
```

The route requirement on the class (`requirements: ['name' => '.+']`, line 14)
permits `/`, and therefore `../`. A request for `/admin/doc/../CLAUDE` serves
`<project_dir>/CLAUDE.md` — a file outside the configured doc directory. The
traversal is not bounded to the project directory, and the percent-encoded form
(`%2e%2e%2f`) is equivalent because `UrlMatcher::match()` calls
`rawurldecode()` on the path before routing, so reverse proxies and browsers
that collapse a literal `../` do not stop it.

Reported by the consuming project bzcrm against version 1.35 (commit
`3207ca8`); the report is reproduced at
`bzcrm:doc/ops/upstream-easy-admin-helper-bundle.md`. Verified against this
repository at HEAD — the report is accurate and current.

Scope of the exposure: every `.md` file readable by the PHP process, for an
audience already authenticated against whatever role gates `/admin`. The `.md`
suffix is appended unconditionally, so no other file type is reachable — but
that bound holds only for as long as the suffix stays hard-coded.

The sibling `DocDiagramController` (line 24) already treats the same class of
input as untrusted, via `preg_replace('#(\.\.|/)#', '', $filename)`. That guard
holds today — with every `/` removed, traversal is inexpressible — but it is a
lexical strip, it silently rewrites input rather than rejecting it, and it means
the bundle carries two different guard styles for one input class.
`DocController` (the listing) cannot itself be used to escape the doc
directory — `Finder::create()->in($this->docDir)` stays rooted there — but a
symlinked doc file that the resolver would reject on the item page still
showed up in the listing, so the index advertised a document that then 404'd.
Filtering the listing through the same resolver (see Components) closes that
gap.

## Approach

One shared, unit-testable path-resolution unit, used by both controllers.
Canonicalize first, then require containment — never a lexical check on the
input, because a lexical check cannot see a symlink inside the doc directory
that points outside it.

## Components

### 1. `src/Doc/DocPathResolver.php` (new)

Stateless, dependency-free, `final`. The single place that turns untrusted
input into a filesystem path.

```php
namespace Playtini\EasyAdminHelperBundle\Doc;

final class DocPathResolver
{
    public function resolveFile(string $baseDir, string $relativePath): ?string;
}
```

Returns the canonical absolute path, or `null` when the input does not name a
readable regular file contained in `$baseDir`. Rules, in order:

1. **Null byte** — reject any `$relativePath` containing `"\0"`. On PHP 8+ the
   filesystem functions throw `ValueError` for null bytes, so `/admin/doc/%00`
   currently produces a 500; this folds it into "Not found".
2. **Canonicalize** — `realpath()` both `$baseDir` and the candidate
   `$baseDir . '/' . $relativePath`. `false` on either → `null`. This resolves
   `..` and symlinks, and folds non-existent paths into the same branch.
3. **Contain** — require `str_starts_with($path, $base . DIRECTORY_SEPARATOR)`.
   The trailing separator prevents a sibling directory named `docs` from
   satisfying a bare prefix match, and rejects the base directory itself (which
   is what an empty `$relativePath` resolves to).
4. **Regular file** — require `is_file($path)`.

Registered as a service so the controllers receive it by constructor injection,
consistent with how `FrontmatterParser` is injected rather than called
statically.

### 2. `src/Controller/Doc/DocItemController.php` (changed)

Inject `DocPathResolver`. Replace the concatenation at line 32:

```php
$filename = $this->docPathResolver->resolveFile($this->docDir, $name . '.md');
if ($filename !== null) {
    $result = $this->frontmatterParser->parseFileToHtml($filename);
    $content = $result->html;
    $data = $result->matter;
}
```

Everything else is unchanged, including `$data['title'] ??=` and the rendered
template. Returning `null` rather than throwing is deliberate: the traversal
case merges into the "Not found" branch that already existed for a missing
document, so no consumer sees a new status code or a new response shape.

**The route requirement `'name' => '.+'` stays as-is.** Containment is the
actual fix and is complete on its own. Tightening the requirement to a safe
charset would hard-404 any consuming project whose doc filenames contain dots,
spaces or non-ASCII characters — a real upgrade break in exchange for defence
in depth that is not needed once the path is contained.

### 3. `src/Controller/Doc/DocDiagramController.php` (changed)

Drop the `preg_replace` strip. Resolve through the same unit and 404 on
rejection:

```php
$path = $this->docPathResolver->resolveFile($this->dbDocDir, $filename);
if ($path === null) {
    throw $this->createNotFoundException();
}

return new BinaryFileResponse($path, public: false);
```

Two intentional behavior changes, both improvements:

- Traversal-shaped input now returns 404 instead of being silently rewritten
  into a different, servable filename.
- A missing diagram now returns 404 instead of 500 — `BinaryFileResponse`
  throws `FileNotFoundException` (a `RuntimeException`) for a non-existent
  path, which is not converted to a 404 by the kernel.

### 4. `src/Controller/Doc/DocController.php` (changed)

Inject `DocPathResolver`. Inside the `foreach` over the Finder results, after
`$name` is computed, skip any file the resolver rejects — resolving
`$name . '.md'` against `$this->docDir`, the same expression
`DocItemController` uses, so the index and the item page can never disagree:

```php
if ($this->docPathResolver->resolveFile($this->docDir, $name . '.md') === null) {
    continue;
}
```

This closes the gap where a symlinked `.md` file (rejected by the resolver
because its target leaves the doc directory) was still listed on the index,
whose link then 404'd on the item page. Grouping, sorting and `$item`
construction are unchanged.

### 5. `config/services.yaml` (changed)

Services in this bundle are registered explicitly; only `src/Controller/` is
glob-registered. Without an entry, autowiring the resolver into the controllers
fails at container compile time:

```yaml
    Playtini\EasyAdminHelperBundle\Doc\DocPathResolver:
        autowire: true
```

Not `public` — it is injected, never fetched from the container.

## Testing

`tests/Doc/DocPathResolverTest.php`, plain PHPUnit `TestCase` against a real
temporary fixture tree, in the style `tests/Frontmatter/FrontmatterParserTest.php`
already uses. No kernel, no `browser-kit`, no new dev dependency.

Fixture layout, created in `setUp()` under a temp directory and removed in
`tearDown()`:

```
<tmp>/base/ops/known-issues.md
<tmp>/base/db/schema.md
<tmp>/base/sub/            (directory, no file)
<tmp>/base/escape          (symlink -> <tmp>/outside)
<tmp>/basedocs/leak.md     (sibling dir whose name extends "base")
<tmp>/outside/secret.md
```

Cases:

| Input | Expected |
|---|---|
| `ops/known-issues.md` | resolves to the fixture file |
| `db/schema.md` | resolves (nested) |
| `db/../ops/known-issues.md` | resolves — a benign `..` that stays inside |
| `../outside/secret.md` | `null` |
| `../../../../outside/secret.md` | `null` — traversal is not bounded to one level |
| `../basedocs/leak.md` | `null` — sibling-prefix directory does not satisfy containment |
| `escape/secret.md` | `null` — symlink out of the base is rejected |
| `sub` | `null` — a directory is not a file |
| `nope.md` | `null` |
| `''` | `null` — resolves to the base directory itself |
| `"\0"`, `"ops/known\0.md"` | `null`, no `ValueError` |

A base directory that does not exist returns `null` for any input.

The CI coverage gate (`bin/coverage-check.php`, 50%) is satisfied by these
tests; the controllers themselves gain no new tests, matching the suite's
existing unit-only shape.

## Verification

- `./vendor/bin/phpunit` — full suite green.
- `./vendor/bin/phpstan` — level 5, no new baseline entries.

## Release

Commit directly on `main` (no branch, no PR, no worktree — see `CLAUDE.md`),
tag `1.36`, push branch and tag. Consuming projects pick the fix up via
composer/satis; bzcrm can then drop its shadowing
`App\Controller\Admin\DocItemController`, which is a separate task in that
repository and out of scope here.

## Out of scope

- Tightening any route requirement.
- Making the `.md` suffix configurable.
- Functional/kernel test infrastructure for this bundle.
- Any change in the bzcrm repository.
