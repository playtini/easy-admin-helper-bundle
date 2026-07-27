# Bundle Extraction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move three converged pieces of code out of the service forks into `playtini/easy-admin-helper-bundle`, release them as 1.34, and adopt them in kobzar and seo-cms without changing either project's database schema.

**Architecture:** The audit-log entity moves as an `#[ORM\MappedSuperclass]` abstract base, so each project keeps its own concrete `App\Entity\AuditLog` with its own indexes, repository and trait choices while inheriting the eight shared columns. The session attribute/listener and the bulk-import form/DTO move as ordinary bundle classes. Adoption is gated on `doctrine:schema:update --dump-sql` producing byte-identical output before and after the change.

**Tech Stack:** PHP 8.5, Symfony 7.4/8.1, Doctrine ORM 3, EasyAdmin 5, PHPUnit 13.2, PHPStan level 5.

**Spec:** `.claude/superpowers/specs/2026-07-27-bundle-extraction-design.md`

## Global Constraints

- Root namespace is `Playtini\EasyAdminHelperBundle`; tests live under `Playtini\EasyAdminHelperBundle\Tests` in `tests/`.
- The bundle runs **PHPStan level 5** (`phpstan.dist.neon`, plus `phpstan-baseline.neon`). New code must not add baseline entries.
- CI enforces a **50% line-coverage gate** (`bin/coverage-check.php var/coverage/clover.xml 50`). Every new `src/` class needs tests.
- `tests/ClassLoadableTest.php` reflects over **every** `.php` file under `src/` and asserts the class loads. Any new class with an unsatisfiable dependency fails it.
- `BaseAuditLog` must use **`CreatableEntityTrait` only**. That trait already does `use IdEntityTrait;` — composing both collides on `getId()`.
- `BaseAuditLog::create()` returns `static` and calls `new static()`. It therefore declares `final public function __construct() {}`, or PHPStan level 5 reports "Unsafe usage of new static()".
- The bulk-import DTO goes in `Form\Dto\`, **not** `Form\Entity\` — a namespace called `Entity` beside the Doctrine `Entity\` namespace is a trap.
- Generated DDL must not change in either fork. The gate is a before/after `diff` of `doctrine:schema:update --dump-sql`, not a judgement call.
- Commit messages use Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`) matching the repo's history.
- `composer.lock` is gitignored in the bundle — never try to commit it there.
- Run `vendor/bin/phpunit`, `vendor/bin/phpstan`, `git commit`, `git tag`, `git push` and `bin/console` with `dangerouslyDisableSandbox: true`.

## Repositories

Tasks 1–4 are in `/Users/vl/www/playtini/easy-admin-helper-bundle`.
Task 5 is in `/Users/vl/www/playtini/kobzar`.
Task 6 is in `/Users/vl/www/playtini/seo-cms`.

Each repository gets its own branch. Suggested names: `feat/bundle-extraction` (bundle), `feat/adopt-base-audit-log` (kobzar), `feat/adopt-bundle-extraction` (seo-cms).

## File Structure

**Bundle — created:**

| File | Responsibility |
| --- | --- |
| `src/Entity/BaseAuditLog.php` | MappedSuperclass: eight audit columns, getters, `create()` factory |
| `src/Attribute/ReleaseSessionEarly.php` | Marker attribute for session-read-only controllers |
| `src/EventListener/ReleaseSessionEarlyListener.php` | Flushes the session before an attributed action runs |
| `src/Form/Dto/BulkImport.php` | TSV-paste DTO: `data`, `mode`, five mode constants, `getRows()` |
| `src/Form/BulkImportType.php` | Symfony form type bound to `BulkImport` |
| `tests/Entity/BaseAuditLogTest.php` | Factory, getters, and column-attribute assertions |
| `tests/Entity/Fixture/ConcreteAuditLog.php` | Minimal concrete subclass for testing the abstract base |
| `tests/EventListener/ReleaseSessionEarlyListenerTest.php` | Five behavioural cases |
| `tests/EventListener/Fixture/AttributedController.php` | Class-level attribute fixture |
| `tests/EventListener/Fixture/MethodAttributedController.php` | Method-level attribute fixture |
| `tests/EventListener/Fixture/PlainController.php` | No-attribute fixture |
| `tests/Form/Dto/BulkImportTest.php` | Parsing, key normalisation, defaults |
| `tests/Form/BulkImportTypeTest.php` | Form option and child assertions |

**Bundle — modified:** `composer.json` (add `luchaninov/csv-file-loader`), `config/services.yaml` (register the listener), `CLAUDE.md` (document the three additions).

**kobzar — modified:** `src/Entity/AuditLog.php`, `composer.json`.

**seo-cms — modified:** `src/Entity/AuditLog.php`, `composer.json`, four dashboard controllers, four import controllers.
**seo-cms — deleted:** `src/Attribute/ReleaseSessionEarly.php`, `src/EventListener/ReleaseSessionEarlyListener.php`, `src/Form/BulkImportType.php`, `src/Form/Entity/BulkImport.php`.

---

## Task 1: `BaseAuditLog` MappedSuperclass

**Repository:** `easy-admin-helper-bundle`

**Files:**
- Create: `src/Entity/BaseAuditLog.php`
- Create: `tests/Entity/Fixture/ConcreteAuditLog.php`
- Create: `tests/Entity/BaseAuditLogTest.php`

**Interfaces:**
- Consumes: `Playtini\EasyAdminHelperBundle\Entity\Traits\CreatableEntityTrait` (existing — supplies `$id`, `getId(): ?int`, `$createdAt`, `getCreatedAt(): DateTimeInterface`, `setCreatedAt(?DateTimeInterface $createdAt = null): self`, `initializeCreatedAt(): void`).
- Produces: `Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog` with `public static function create(string $username, string $routeName, ?array $routeParams, string $url, string $httpMethod, int $statusCode, string $ip, int $responseTimeMs): static` and the getters `getUsername(): string`, `getRouteName(): string`, `getRouteParams(): ?array`, `getUrl(): string`, `getHttpMethod(): string`, `getStatusCode(): int`, `getIp(): string`, `getResponseTimeMs(): int`. Tasks 5 and 6 extend this class.

---

- [ ] **Step 1: Create the test fixture subclass**

`BaseAuditLog` is abstract, so the tests need something concrete to instantiate. Keep it in its own file so PSR-4 autoloading works.

Create `tests/Entity/Fixture/ConcreteAuditLog.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Entity\Fixture;

use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;

/**
 * Stands in for a project's own App\Entity\AuditLog. Deliberately adds nothing:
 * the point is to prove BaseAuditLog is usable with an empty subclass.
 */
class ConcreteAuditLog extends BaseAuditLog
{
}
```

- [ ] **Step 2: Write the failing test**

Create `tests/Entity/BaseAuditLogTest.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;
use Playtini\EasyAdminHelperBundle\Tests\Entity\Fixture\ConcreteAuditLog;
use ReflectionClass;
use ReflectionProperty;

class BaseAuditLogTest extends TestCase
{
    public function testCreatePopulatesEveryField(): void
    {
        $item = ConcreteAuditLog::create(
            'alice',
            'admin_dashboard',
            ['page' => 2],
            'https://example.test/admin?page=2',
            'GET',
            200,
            '10.0.0.1',
            42,
        );

        $this->assertSame('alice', $item->getUsername());
        $this->assertSame('admin_dashboard', $item->getRouteName());
        $this->assertSame(['page' => 2], $item->getRouteParams());
        $this->assertSame('https://example.test/admin?page=2', $item->getUrl());
        $this->assertSame('GET', $item->getHttpMethod());
        $this->assertSame(200, $item->getStatusCode());
        $this->assertSame('10.0.0.1', $item->getIp());
        $this->assertSame(42, $item->getResponseTimeMs());
    }

    public function testCreateReturnsTheConcreteSubclassNotTheBase(): void
    {
        $item = ConcreteAuditLog::create('a', 'r', null, 'u', 'GET', 200, '1.2.3.4', 1);

        $this->assertInstanceOf(ConcreteAuditLog::class, $item);
    }

    public function testCreateAcceptsNullRouteParams(): void
    {
        $item = ConcreteAuditLog::create('a', 'r', null, 'u', 'GET', 200, '1.2.3.4', 1);

        $this->assertNull($item->getRouteParams());
    }

    public function testDefaultsAreEmptyBeforeCreate(): void
    {
        $item = new ConcreteAuditLog();

        $this->assertSame('', $item->getUsername());
        $this->assertSame('', $item->getRouteName());
        $this->assertNull($item->getRouteParams());
        $this->assertSame('', $item->getUrl());
        $this->assertSame('', $item->getHttpMethod());
        $this->assertSame(0, $item->getStatusCode());
        $this->assertSame('', $item->getIp());
        $this->assertSame(0, $item->getResponseTimeMs());
    }

    public function testIdComesFromCreatableEntityTraitAndIsNullBeforePersist(): void
    {
        $item = new ConcreteAuditLog();

        $this->assertNull($item->getId());
    }

    public function testCreatedAtLifecycleCallbackIsInherited(): void
    {
        $item = new ConcreteAuditLog();
        $item->initializeCreatedAt();

        $this->assertInstanceOf(DateTimeInterface::class, $item->getCreatedAt());
    }

    public function testIsMappedSuperclass(): void
    {
        $attributes = new ReflectionClass(BaseAuditLog::class)->getAttributes(ORM\MappedSuperclass::class);

        $this->assertCount(1, $attributes);
    }

    public function testIsAbstract(): void
    {
        $this->assertTrue(new ReflectionClass(BaseAuditLog::class)->isAbstract());
    }

    /**
     * Guards the claim the adoption gate rests on: these column definitions are
     * copied from the forks' inlined AuditLog entities, so the generated DDL is
     * unchanged. A change here is a schema change in every adopting project.
     *
     * @param array<string, mixed> $expected
     */
    #[DataProvider('columnProvider')]
    public function testColumnDefinitionMatchesTheForkSchema(string $property, array $expected): void
    {
        $attributes = new ReflectionProperty(BaseAuditLog::class, $property)
            ->getAttributes(ORM\Column::class);
        $this->assertCount(1, $attributes, sprintf('%s has no #[ORM\Column]', $property));

        $column = $attributes[0]->newInstance();

        $this->assertSame($expected['type'] ?? null, $column->type, sprintf('%s type', $property));
        $this->assertSame($expected['length'] ?? null, $column->length, sprintf('%s length', $property));
        $this->assertSame($expected['nullable'] ?? false, $column->nullable, sprintf('%s nullable', $property));
    }

    /** @return iterable<string, array{string, array<string, mixed>}> */
    public static function columnProvider(): iterable
    {
        yield 'username' => ['username', ['length' => 255]];
        yield 'routeName' => ['routeName', ['length' => 255]];
        yield 'routeParams' => ['routeParams', ['type' => Types::JSON, 'nullable' => true]];
        yield 'url' => ['url', ['length' => 2048]];
        yield 'httpMethod' => ['httpMethod', ['length' => 10]];
        yield 'statusCode' => ['statusCode', ['type' => Types::SMALLINT]];
        yield 'ip' => ['ip', ['length' => 45]];
        yield 'responseTimeMs' => ['responseTimeMs', []];
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Entity/BaseAuditLogTest.php`
Expected: FAIL — `Class "Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog" does not exist`.

- [ ] **Step 4: Write the implementation**

Create `src/Entity/BaseAuditLog.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Playtini\EasyAdminHelperBundle\Entity\Traits\CreatableEntityTrait;

/**
 * Shared shape for per-request audit-log entities.
 *
 * A project subclasses this with its own #[ORM\Entity], repository binding and
 * indexes, so the physical table stays under the project's control while the
 * eight columns below stay identical everywhere:
 *
 *     #[ORM\Entity(repositoryClass: AuditLogRepository::class)]
 *     #[ORM\HasLifecycleCallbacks]
 *     #[ORM\Index(columns: ['created_at'])]
 *     class AuditLog extends BaseAuditLog {}
 *
 * #[ORM\HasLifecycleCallbacks] must be declared on the concrete class or
 * CreatableEntityTrait::initializeCreatedAt() never fires.
 *
 * CreatableEntityTrait already composes IdEntityTrait, so $id and getId() come
 * from it. Do not also `use IdEntityTrait` in a subclass — the two collide.
 */
#[ORM\MappedSuperclass]
abstract class BaseAuditLog
{
    use CreatableEntityTrait;

    #[ORM\Column(length: 255)]
    private string $username = '';

    #[ORM\Column(length: 255)]
    private string $routeName = '';

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $routeParams = null;

    #[ORM\Column(length: 2048)]
    private string $url = '';

    #[ORM\Column(length: 10)]
    private string $httpMethod = '';

    #[ORM\Column(type: Types::SMALLINT)]
    private int $statusCode = 0;

    #[ORM\Column(length: 45)]
    private string $ip = '';

    #[ORM\Column]
    private int $responseTimeMs = 0;

    /**
     * Final so that `new static()` below is safe for every subclass. Doctrine
     * hydrates through newInstanceWithoutConstructor() and never calls it.
     */
    final public function __construct()
    {
    }

    /** @param array<string, mixed>|null $routeParams */
    public static function create(
        string $username,
        string $routeName,
        ?array $routeParams,
        string $url,
        string $httpMethod,
        int $statusCode,
        string $ip,
        int $responseTimeMs,
    ): static {
        $item = new static();
        $item->username = $username;
        $item->routeName = $routeName;
        $item->routeParams = $routeParams;
        $item->url = $url;
        $item->httpMethod = $httpMethod;
        $item->statusCode = $statusCode;
        $item->ip = $ip;
        $item->responseTimeMs = $responseTimeMs;

        return $item;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    /** @return array<string, mixed>|null */
    public function getRouteParams(): ?array
    {
        return $this->routeParams;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getResponseTimeMs(): int
    {
        return $this->responseTimeMs;
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/Entity/BaseAuditLogTest.php`
Expected: PASS, 16 tests (8 plain + 8 from the data provider).

- [ ] **Step 6: Run the full suite and static analysis**

Run: `vendor/bin/phpunit`
Expected: PASS. `ClassLoadableTest` picks up `BaseAuditLog` automatically — it must load.

Run: `vendor/bin/phpstan`
Expected: `[OK] No errors`. If it reports "Unsafe usage of new static()", the `final` keyword is missing from `__construct()` — add it rather than adding a baseline entry.

- [ ] **Step 7: Commit**

```bash
git add src/Entity/BaseAuditLog.php tests/Entity/BaseAuditLogTest.php tests/Entity/Fixture/ConcreteAuditLog.php
git commit -m "feat(entity): add BaseAuditLog mapped superclass"
```

---

## Task 2: `ReleaseSessionEarly` attribute and listener

**Repository:** `easy-admin-helper-bundle`

**Files:**
- Create: `src/Attribute/ReleaseSessionEarly.php`
- Create: `src/EventListener/ReleaseSessionEarlyListener.php`
- Create: `tests/EventListener/Fixture/AttributedController.php`
- Create: `tests/EventListener/Fixture/MethodAttributedController.php`
- Create: `tests/EventListener/Fixture/PlainController.php`
- Create: `tests/EventListener/ReleaseSessionEarlyListenerTest.php`
- Modify: `config/services.yaml`

**Interfaces:**
- Consumes: nothing from Task 1.
- Produces: `Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly` (a marker attribute, `TARGET_CLASS | TARGET_METHOD`, no constructor arguments) and `Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener` (invokable, `__invoke(ControllerEvent $event): void`). Task 6 repoints seo-cms at both.

---

- [ ] **Step 1: Create the three controller fixtures**

The listener decides by reflecting over the controller, so the tests need real classes carrying (and not carrying) the attribute.

Create `tests/EventListener/Fixture/AttributedController.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;

#[ReleaseSessionEarly]
class AttributedController
{
    public function __invoke(): void
    {
    }

    public function someAction(): void
    {
    }
}
```

Create `tests/EventListener/Fixture/MethodAttributedController.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;

class MethodAttributedController
{
    #[ReleaseSessionEarly]
    public function attributedAction(): void
    {
    }

    public function plainAction(): void
    {
    }
}
```

Create `tests/EventListener/Fixture/PlainController.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture;

class PlainController
{
    public function __invoke(): void
    {
    }
}
```

- [ ] **Step 2: Write the failing test**

Create `tests/EventListener/ReleaseSessionEarlyListenerTest.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\AttributedController;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\MethodAttributedController;
use Playtini\EasyAdminHelperBundle\Tests\EventListener\Fixture\PlainController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ReleaseSessionEarlyListenerTest extends TestCase
{
    public function testSavesStartedSessionForClassLevelAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testSavesStartedSessionForMethodLevelAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(1);
        $controller = [new MethodAttributedController(), 'attributedAction'];
        $event = $this->event($controller, $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresUnattributedMethodOnAnUnattributedClass(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $controller = [new MethodAttributedController(), 'plainAction'];
        $event = $this->event($controller, $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresControllerWithoutAttribute(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $event = $this->event(new PlainController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresSubRequest(): void
    {
        $session = $this->startedSessionExpectingSave(0);
        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::SUB_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresUnstartedSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('isStarted')->willReturn(false);
        $session->expects($this->never())->method('save');

        $event = $this->event(new AttributedController(), $session, HttpKernelInterface::MAIN_REQUEST);

        new ReleaseSessionEarlyListener()($event);
    }

    public function testIgnoresRequestWithoutSession(): void
    {
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            new AttributedController(),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
        );

        new ReleaseSessionEarlyListener()($event);

        $this->assertFalse($event->getRequest()->hasSession());
    }

    private function startedSessionExpectingSave(int $times): SessionInterface
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('isStarted')->willReturn(true);
        $session->expects($this->exactly($times))->method('save');

        return $session;
    }

    private function event(mixed $controller, SessionInterface $session, int $requestType): ControllerEvent
    {
        $request = new Request();
        $request->setSession($session);

        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            $requestType,
        );
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/EventListener/ReleaseSessionEarlyListenerTest.php`
Expected: FAIL — `Class "Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly" does not exist`.

- [ ] **Step 4: Write the attribute**

Create `src/Attribute/ReleaseSessionEarly.php`. This is tds's version with the `{@see}` reference repointed at the bundle and the tds-specific `lock_mode: 0` sentence removed:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Attribute;

use Attribute;

/**
 * Mark a controller (class or method) as session-read-only so the
 * {@see \Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener}
 * flushes the session before the action runs, preventing the session row from
 * being held while concurrent XHRs hit the same user.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ReleaseSessionEarly
{
}
```

- [ ] **Step 5: Write the listener**

Create `src/EventListener/ReleaseSessionEarlyListener.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\EventListener;

use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * If a controller carries the {@see ReleaseSessionEarly} attribute, flush the
 * Symfony session (`$session->save()`) before the action runs. The action is
 * then free to do its work without the session row being held — important for
 * concurrent XHRs from the same user.
 *
 * Sessions are never started here: if the controller hasn't started one, no
 * save happens. The listener is inert in a project that uses the attribute
 * nowhere.
 */
#[AsEventListener(event: ControllerEvent::class)]
final readonly class ReleaseSessionEarlyListener
{
    public function __invoke(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->controllerHasAttribute($event->getController())) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if ($session->isStarted()) {
            $session->save();
        }
    }

    private function controllerHasAttribute(mixed $controller): bool
    {
        if (is_array($controller) && count($controller) === 2 && is_object($controller[0]) && is_string($controller[1])) {
            [$object, $method] = $controller;
            $reflectionMethod = new ReflectionMethod($object, $method);
            if ($reflectionMethod->getAttributes(ReleaseSessionEarly::class) !== []) {
                return true;
            }

            return (new ReflectionClass($object))->getAttributes(ReleaseSessionEarly::class) !== [];
        }

        if (is_object($controller)) {
            return (new ReflectionClass($controller))->getAttributes(ReleaseSessionEarly::class) !== [];
        }

        return false;
    }
}
```

- [ ] **Step 6: Register the listener as a service**

`config/services.yaml` currently registers only `Dashboard\`, `Frontmatter\` and `Controller\`. The listener needs `autoconfigure: true` so Symfony honours `#[AsEventListener]`. Append this entry after the `Frontmatter\FrontmatterParser` block, before the `Controller\` block:

```yaml
    Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener:
        autoconfigure: true
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/EventListener/ReleaseSessionEarlyListenerTest.php`
Expected: PASS, 7 tests.

- [ ] **Step 8: Run the full suite and static analysis**

Run: `vendor/bin/phpunit`
Expected: PASS.

Run: `vendor/bin/phpstan`
Expected: `[OK] No errors`.

- [ ] **Step 9: Commit**

```bash
git add src/Attribute src/EventListener config/services.yaml tests/EventListener
git commit -m "feat(session): add ReleaseSessionEarly attribute and listener"
```

---

## Task 3: `BulkImport` DTO and form type

**Repository:** `easy-admin-helper-bundle`

**Files:**
- Modify: `composer.json` (add `luchaninov/csv-file-loader`)
- Create: `src/Form/Dto/BulkImport.php`
- Create: `src/Form/BulkImportType.php`
- Create: `tests/Form/Dto/BulkImportTest.php`
- Create: `tests/Form/BulkImportTypeTest.php`

**Interfaces:**
- Consumes: `Luchaninov\CsvFileLoader\TsvStringLoader` — constructor takes the raw TSV string; `getItems(): Generator` treats the first non-empty line as headers, skips blank lines, and yields each row as an array combined with those headers.
- Produces: `Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport` with constants `MODE_CREATE_OR_UPDATE`, `MODE_CREATE_ONLY`, `MODE_UPDATE_ONLY`, `MODE_CREATE_SKIP_EXISTING`, `MODE_UPDATE_SKIP_MISSING`, and methods `getData(): string`, `setData(string $data): static`, `getMode(): string`, `setMode(string $mode): static`, `getRows(): array`. Also `Playtini\EasyAdminHelperBundle\Form\BulkImportType`. Task 6 repoints seo-cms at both.

---

- [ ] **Step 1: Add the composer dependency**

`Luchaninov\CsvFileLoader\TsvStringLoader` is not currently in the bundle's dependency tree — it will not resolve without this.

```bash
composer require luchaninov/csv-file-loader:^1.10
```

Verify: `ls vendor/luchaninov/csv-file-loader` exists, and `composer.json` `require` now contains `"luchaninov/csv-file-loader": "^1.10"`. Do not attempt to commit `composer.lock` — it is gitignored in this repo.

- [ ] **Step 2: Write the failing DTO test**

Create `tests/Form/Dto/BulkImportTest.php`:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Form\Dto;

use PHPUnit\Framework\TestCase;
use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;

class BulkImportTest extends TestCase
{
    public function testDataDefaultsToEmptyStringInsteadOfThrowing(): void
    {
        $this->assertSame('', new BulkImport()->getData());
    }

    public function testModeDefaultsToCreateOrUpdate(): void
    {
        $this->assertSame(BulkImport::MODE_CREATE_OR_UPDATE, new BulkImport()->getMode());
    }

    public function testSettersAreFluent(): void
    {
        $item = new BulkImport();

        $this->assertSame($item, $item->setData("a\tb\n1\t2"));
        $this->assertSame($item, $item->setMode(BulkImport::MODE_CREATE_ONLY));
        $this->assertSame(BulkImport::MODE_CREATE_ONLY, $item->getMode());
    }

    public function testGetRowsParsesTsvUsingTheFirstLineAsHeaders(): void
    {
        $item = new BulkImport()->setData("domain\tip\nexample.test\t10.0.0.1\nother.test\t10.0.0.2");

        $this->assertSame([
            ['domain' => 'example.test', 'ip' => '10.0.0.1'],
            ['domain' => 'other.test', 'ip' => '10.0.0.2'],
        ], $item->getRows());
    }

    public function testGetRowsLowercasesAndTrimsHeaderKeys(): void
    {
        $item = new BulkImport()->setData("  Domain  \t IP \nexample.test\t10.0.0.1");

        $this->assertSame([['domain' => 'example.test', 'ip' => '10.0.0.1']], $item->getRows());
    }

    public function testGetRowsSkipsBlankLines(): void
    {
        $item = new BulkImport()->setData("domain\nexample.test\n\nother.test\n");

        $this->assertSame([['domain' => 'example.test'], ['domain' => 'other.test']], $item->getRows());
    }

    public function testGetRowsReturnsEmptyArrayWhenOnlyHeadersArePresent(): void
    {
        $item = new BulkImport()->setData("domain\tip");

        $this->assertSame([], $item->getRows());
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Form/Dto/BulkImportTest.php`
Expected: FAIL — `Class "Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport" does not exist`.

- [ ] **Step 4: Write the DTO**

Create `src/Form/Dto/BulkImport.php`. This is seo-cms's class with three changes: the namespace, `$data` initialised to `''` (as written it throws on `getData()` before `setData()`), and the empty constructor deleted.

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Form\Dto;

use Luchaninov\CsvFileLoader\TsvStringLoader;

/**
 * Backing DTO for {@see \Playtini\EasyAdminHelperBundle\Form\BulkImportType}:
 * a pasted TSV blob plus the mode that decides what to do with each row.
 *
 * The modes are declared here rather than in the importing controller so every
 * project offers the same five, with the same stored values.
 */
class BulkImport
{
    public const string MODE_CREATE_OR_UPDATE = 'create_or_update';
    public const string MODE_CREATE_ONLY = 'create_only';
    public const string MODE_UPDATE_ONLY = 'update_only';
    public const string MODE_CREATE_SKIP_EXISTING = 'create_skip_existing';
    public const string MODE_UPDATE_SKIP_MISSING = 'update_skip_missing';

    private string $data = '';
    private string $mode = self::MODE_CREATE_OR_UPDATE;

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setMode(string $mode): static
    {
        $this->mode = $mode;

        return $this;
    }

    /** @return list<array<array-key, mixed>> */
    public function getRows(): array
    {
        $result = [];

        $loader = new TsvStringLoader($this->data);
        foreach ($loader->getItems() as $row) {
            if ($row) {
                $result[] = self::normalizeKeys($row);
            }
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    private static function normalizeKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[is_string($key) ? strtolower(trim($key)) : $key] = $value;
        }

        return $normalized;
    }
}
```

- [ ] **Step 5: Run the DTO test to verify it passes**

Run: `vendor/bin/phpunit tests/Form/Dto/BulkImportTest.php`
Expected: PASS, 7 tests.

- [ ] **Step 6: Write the failing form-type test**

Create `tests/Form/BulkImportTypeTest.php`. Extend Symfony's `TypeTestCase`, which builds a form factory without booting a kernel:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Tests\Form;

use Playtini\EasyAdminHelperBundle\Form\BulkImportType;
use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;
use Symfony\Component\Form\Test\TypeTestCase;

class BulkImportTypeTest extends TypeTestCase
{
    public function testBuildsDataModeAndSaveChildren(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertTrue($form->has('data'));
        $this->assertTrue($form->has('mode'));
        $this->assertTrue($form->has('save'));
    }

    public function testIsBoundToTheBulkImportDto(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertSame(BulkImport::class, $form->getConfig()->getDataClass());
    }

    public function testOffersTheFiveImportModes(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $this->assertSame([
            'Create or update' => BulkImport::MODE_CREATE_OR_UPDATE,
            'Create only (error if exists)' => BulkImport::MODE_CREATE_ONLY,
            'Update only (error if missing)' => BulkImport::MODE_UPDATE_ONLY,
            'Create and skip existing' => BulkImport::MODE_CREATE_SKIP_EXISTING,
            'Update and skip missing' => BulkImport::MODE_UPDATE_SKIP_MISSING,
        ], $form->get('mode')->getConfig()->getOption('choices'));
    }

    public function testSubmittingPopulatesTheDto(): void
    {
        $form = $this->factory->create(BulkImportType::class);

        $form->submit(['data' => "domain\nexample.test", 'mode' => BulkImport::MODE_CREATE_ONLY]);

        $this->assertTrue($form->isSynchronized());
        $item = $form->getData();
        $this->assertInstanceOf(BulkImport::class, $item);
        $this->assertSame("domain\nexample.test", $item->getData());
        $this->assertSame(BulkImport::MODE_CREATE_ONLY, $item->getMode());
    }
}
```

- [ ] **Step 7: Run the test to verify it fails**

Run: `vendor/bin/phpunit tests/Form/BulkImportTypeTest.php`
Expected: FAIL — `Class "Playtini\EasyAdminHelperBundle\Form\BulkImportType" does not exist`.

- [ ] **Step 8: Write the form type**

Create `src/Form/BulkImportType.php`. Verbatim from seo-cms apart from the namespaces and the deleted empty constructor:

```php
<?php

declare(strict_types=1);

namespace Playtini\EasyAdminHelperBundle\Form;

use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Paste-a-TSV bulk import form. Deliberately knows nothing about what is being
 * imported: the controller reads {@see BulkImport::getRows()} and decides what
 * each row means.
 */
class BulkImportType extends AbstractType
{
    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('data', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'rows' => 20,
                    'wrap' => 'off',
                ],
                'help' => 'TSV',
            ])
            ->add('mode', ChoiceType::class, [
                'choices' => [
                    'Create or update' => BulkImport::MODE_CREATE_OR_UPDATE,
                    'Create only (error if exists)' => BulkImport::MODE_CREATE_ONLY,
                    'Update only (error if missing)' => BulkImport::MODE_UPDATE_ONLY,
                    'Create and skip existing' => BulkImport::MODE_CREATE_SKIP_EXISTING,
                    'Update and skip missing' => BulkImport::MODE_UPDATE_SKIP_MISSING,
                ],
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Import',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BulkImport::class,
        ]);
    }
}
```

- [ ] **Step 9: Run the test to verify it passes**

Run: `vendor/bin/phpunit tests/Form/BulkImportTypeTest.php`
Expected: PASS, 4 tests.

- [ ] **Step 10: Run the full suite and static analysis**

Run: `vendor/bin/phpunit`
Expected: PASS. `ClassLoadableTest` now also reflects `BulkImport` and `BulkImportType` — if `luchaninov/csv-file-loader` was not installed in Step 1, this is where it surfaces.

Run: `vendor/bin/phpstan`
Expected: `[OK] No errors`.

- [ ] **Step 11: Commit**

```bash
git add composer.json src/Form tests/Form
git commit -m "feat(form): add BulkImport DTO and form type"
```

---

## Task 4: Document and release 1.34

**Repository:** `easy-admin-helper-bundle`

**Files:**
- Modify: `CLAUDE.md`

**Interfaces:**
- Consumes: everything from Tasks 1–3.
- Produces: git tag `1.34` on `main`, indexed by satis. Tasks 5 and 6 install it.

---

- [ ] **Step 1: Document the new classes in `CLAUDE.md`**

`CLAUDE.md` has an `## Architecture` section listing what the bundle provides. Add these three subsections after the existing `### Entity Traits (src/Entity/Traits/)` block:

```markdown
### Entity Base Classes (src/Entity/)
- `BaseAuditLog` - `#[ORM\MappedSuperclass]` carrying the eight columns of a per-request audit log (`username`, `routeName`, `routeParams`, `url`, `httpMethod`, `statusCode`, `ip`, `responseTimeMs`) plus id/createdAt via `CreatableEntityTrait`. Projects subclass it with their own `#[ORM\Entity]`, indexes and repository. The concrete class must declare `#[ORM\HasLifecycleCallbacks]`. Do not also `use IdEntityTrait` — `CreatableEntityTrait` already composes it.

### Session Handling (src/Attribute/, src/EventListener/)
- `ReleaseSessionEarly` - Marker attribute for a controller class or method.
- `ReleaseSessionEarlyListener` - Flushes the session before an attributed action runs, so the session row is not held for the duration. Inert when the attribute is unused.

### Forms (src/Form/)
- `BulkImportType` / `Form\Dto\BulkImport` - Paste-a-TSV import form with five create/update modes. The form knows nothing about the target entity; the controller reads `BulkImport::getRows()` and decides what each row means.
```

- [ ] **Step 2: Commit the docs**

```bash
git add CLAUDE.md
git commit -m "docs: document BaseAuditLog, ReleaseSessionEarly and BulkImport"
```

- [ ] **Step 3: Merge to main and verify CI**

Push the branch, open the PR, and confirm the `PR Check` workflow is green — it runs PHPStan on `src/`, PHPUnit with coverage, and the 50% coverage gate. Merge to `main` once green.

```bash
git push -u origin feat/bundle-extraction
gh pr create --fill
```

- [ ] **Step 4: Tag and push the release**

From `main`, with the merge commit checked out:

```bash
git checkout main && git pull
git tag 1.34
git push origin 1.34
```

- [ ] **Step 5: Wait for satis to index the tag**

The satis index on `tools.s.p777.org` rebuilds every 15 minutes via cron. Confirm 1.34 is installable before starting Task 5:

```bash
curl -s https://satis.p777.org/p2/playtini/easy-admin-helper-bundle.json | grep -o '"version":"1\.34"'
```

Expected: `"version":"1.34"`. If it is absent, wait for the next quarter-hour and retry. Do not start Task 5 until it appears — `composer update` in the forks will silently resolve to 1.33 instead.

---

## Task 5: kobzar adoption

**Repository:** `/Users/vl/www/playtini/kobzar`

**Files:**
- Modify: `composer.json` (bundle constraint `^1.32` → `^1.34`)
- Modify: `src/Entity/AuditLog.php` (135 lines → 15)

**Interfaces:**
- Consumes: `Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog` and its `create()` signature from Task 1; `Playtini\EasyAdminHelperBundle\Entity\Traits\VirtualFieldsEntityTrait`.
- Produces: nothing later tasks depend on. Task 6 is independent of this one.

**Context the implementer needs:**

- kobzar's `App\Entity\Traits\{IdTrait,CreatableEntityTrait,VirtualFieldsEntityTrait}` are used by 7, 5 and 5 entities respectively. **Do not delete them.** `AuditLog` alone stops using them; the rest of the project keeps them. Migrating kobzar off its local traits wholesale is explicitly out of scope.
- `src/EventListener/AuditLogListener.php:71` calls `AuditLog::create(...)` with eight positional arguments. The signature is unchanged, so that call site needs no edit.
- `src/EasyAdmin/CrudField.php` binds `TextField::new('virtualString')` for both `routeParams()` and `statusCodeBadge()`. `virtualString()` comes from `VirtualFieldsEntityTrait`, which is why `AuditLog` must keep using it.
- kobzar's `IdTrait` and `CreatableEntityTrait` carry `#[Groups(['id:Read'])]` / `#[Groups(['created:Read'])]`. A grep of `src/` and `config/` finds no consumer of either group, so losing them is inert.

---

- [ ] **Step 1: Start the containers and capture the baseline schema dump**

The gate is a before/after comparison, so the "before" must be captured while `AuditLog` is still the old inlined class.

```bash
make run
docker compose -p kobzar -f ./docker/compose.yaml exec app \
  /app/bin/console doctrine:schema:update --dump-sql > /tmp/kobzar-schema-before.txt 2>&1
cat /tmp/kobzar-schema-before.txt
```

Expected: either `[OK] Nothing to update` or a list of statements. Either is fine — what matters is that the "after" output is identical. If the command errors (containers not up, DB missing), run `make all` first.

- [ ] **Step 2: Update the bundle and bump the constraint**

Edit `composer.json`, changing `"playtini/easy-admin-helper-bundle": "^1.32"` to `"playtini/easy-admin-helper-bundle": "^1.34"`, then:

```bash
docker compose -p kobzar -f ./docker/compose.yaml exec app \
  composer update playtini/easy-admin-helper-bundle --ignore-platform-reqs --no-scripts
```

Verify the installed version:

```bash
docker compose -p kobzar -f ./docker/compose.yaml exec app \
  composer show playtini/easy-admin-helper-bundle | grep versions
```

Expected: `versions : * 1.34`. If it resolved to 1.33, satis has not indexed the tag — go back to Task 4 Step 5.

- [ ] **Step 3: Rewrite `AuditLog` to extend the base**

Replace the entire contents of `src/Entity/AuditLog.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;
use Playtini\EasyAdminHelperBundle\Entity\Traits\VirtualFieldsEntityTrait;

/**
 * The eight audit columns, the getters and create() live in BaseAuditLog.
 * Indexes, the repository binding and HasLifecycleCallbacks stay here because
 * they are this project's physical choices.
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['route_name'])]
class AuditLog extends BaseAuditLog
{
    use VirtualFieldsEntityTrait;
}
```

- [ ] **Step 4: Run the schema gate**

```bash
docker compose -p kobzar -f ./docker/compose.yaml exec app \
  /app/bin/console doctrine:schema:update --dump-sql > /tmp/kobzar-schema-after.txt 2>&1
diff /tmp/kobzar-schema-before.txt /tmp/kobzar-schema-after.txt && echo "SCHEMA UNCHANGED"
```

Expected: `SCHEMA UNCHANGED`, with `diff` producing no output.

**If the diff is non-empty, stop and report it. Do not write a migration.** A difference means `BaseAuditLog` does not match what kobzar actually has, and the fix belongs in the bundle (Task 1), not in a migration that would push the bundle's shape onto a production table under the guise of a refactor.

- [ ] **Step 5: Run the tests and static analysis**

```bash
make test
vendor/bin/phpstan analyze
```

Expected: both pass, with the same counts as before the change.

- [ ] **Step 6: Verify the audit-log admin page still renders**

The CRUD controller binds virtual fields that read the entity through `formatValue`. A missing `VirtualFieldsEntityTrait` or a changed getter would only show up at render time, not in the test suite.

Open `http://localhost.net:8000`, visit the AuditLog list and detail pages, and confirm the Status badge, Params and URL columns render as before.

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock src/Entity/AuditLog.php
git commit -m "refactor(entity): extend BaseAuditLog from the bundle

The eight audit columns, getters and create() factory now come from
Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog. Indexes, the repository
binding and HasLifecycleCallbacks stay here. doctrine:schema:update --dump-sql
is byte-identical before and after, so no migration is needed."
```

- [ ] **Step 8: Open the PR**

```bash
git push -u origin feat/adopt-base-audit-log
gh pr create --fill
```

---

## Task 6: seo-cms adoption

**Repository:** `/Users/vl/www/playtini/seo-cms`

**Files:**
- Modify: `composer.json` (bundle constraint `^1.25` → `^1.34`)
- Modify: `src/Entity/AuditLog.php` (132 lines → 14)
- Delete: `src/Attribute/ReleaseSessionEarly.php`
- Delete: `src/EventListener/ReleaseSessionEarlyListener.php`
- Delete: `src/Form/BulkImportType.php`
- Delete: `src/Form/Entity/BulkImport.php`
- Modify: `src/Controller/Admin/Dashboard/StatController.php`
- Modify: `src/Controller/Admin/Dashboard/ProblemsController.php`
- Modify: `src/Controller/Admin/Dashboard/ResponseTimeController.php`
- Modify: `src/Controller/Admin/Dashboard/TopSitesController.php`
- Modify: `src/Controller/Admin/HostImportController.php`
- Modify: `src/Controller/Admin/SiteImportController.php`
- Modify: `src/Controller/Admin/ProxyImportController.php`
- Modify: `src/Controller/Admin/CloudflareAccountImportController.php`

**Interfaces:**
- Consumes: `Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog` (Task 1), `Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly` (Task 2), `Playtini\EasyAdminHelperBundle\Form\BulkImportType` and `Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport` (Task 3).
- Produces: nothing.

**Context the implementer needs:**

- seo-cms's `App\Entity\Traits\{IdTrait,CreatableEntityTrait,VirtualFieldsEntityTrait}` are used by 28, 29 and 17 entities respectively. **Do not delete them.**
- seo-cms's `AuditLog` does **not** use `VirtualFieldsEntityTrait` and must not gain it — its `CrudField::routeParams()` binds the real `routeParams` property, unlike kobzar's.
- `src/EventListener/AuditLogListener.php:112` calls `AuditLog::create(...)` with eight positional arguments. Unchanged signature, no edit needed.
- The four import controllers construct the form with `$this->createFormBuilder()` or `createForm(BulkImportType::class)` and read `BulkImport::getRows()`/`getMode()`. Only their `use` statements change — the controllers themselves stay in seo-cms because each knows which entity it builds and what each mode means for it.
- seo-cms runs PHPStan at `--level 2` (`make analyze`), not the bundle's level 5.

---

- [ ] **Step 1: Start the containers and capture the baseline schema dump**

```bash
make run
docker compose -p seo_cms -f ./docker/compose.yaml exec app \
  /app/bin/console doctrine:schema:update --dump-sql > /tmp/seo-cms-schema-before.txt 2>&1
cat /tmp/seo-cms-schema-before.txt
```

If the command errors because the containers or database are missing, run `make all` first.

- [ ] **Step 2: Update the bundle and bump the constraint**

Edit `composer.json`, changing `"playtini/easy-admin-helper-bundle": "^1.25"` to `"playtini/easy-admin-helper-bundle": "^1.34"`, then:

```bash
docker compose -p seo_cms -f ./docker/compose.yaml exec app \
  composer update playtini/easy-admin-helper-bundle --ignore-platform-reqs --no-scripts

docker compose -p seo_cms -f ./docker/compose.yaml exec app \
  composer show playtini/easy-admin-helper-bundle | grep versions
```

Expected: `versions : * 1.34`.

- [ ] **Step 3: Check for serializer-group consumers before dropping them**

seo-cms's local traits carry `#[Groups(['id:Read'])]` / `#[Groups(['created:Read'])]`, which the bundle's traits do not. Confirm nothing reads them:

```bash
grep -rn "id:Read\|created:Read\|normalizationContext\|'groups'" src/ config/ | grep -v 'src/Entity/Traits/'
```

Expected: no output. If there is a consumer, re-declare the group on the concrete `AuditLog` property rather than adding it to the bundle, and note it in the PR.

- [ ] **Step 4: Rewrite `AuditLog` to extend the base**

Replace the entire contents of `src/Entity/AuditLog.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog;

/**
 * The eight audit columns, the getters and create() live in BaseAuditLog.
 * Indexes, the repository binding and HasLifecycleCallbacks stay here because
 * they are this project's physical choices.
 */
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['route_name'])]
class AuditLog extends BaseAuditLog
{
}
```

- [ ] **Step 5: Run the schema gate**

```bash
docker compose -p seo_cms -f ./docker/compose.yaml exec app \
  /app/bin/console doctrine:schema:update --dump-sql > /tmp/seo-cms-schema-after.txt 2>&1
diff /tmp/seo-cms-schema-before.txt /tmp/seo-cms-schema-after.txt && echo "SCHEMA UNCHANGED"
```

Expected: `SCHEMA UNCHANGED`.

**If the diff is non-empty, stop and report it. Do not write a migration.**

- [ ] **Step 6: Commit the entity change on its own**

Keeping the entity change in its own commit means the schema gate has a single commit to point at if it ever has to be bisected.

```bash
git add composer.json composer.lock src/Entity/AuditLog.php
git commit -m "refactor(entity): extend BaseAuditLog from the bundle"
```

- [ ] **Step 7: Repoint the four dashboard controllers at the bundle's attribute**

In each of `src/Controller/Admin/Dashboard/{StatController,ProblemsController,ResponseTimeController,TopSitesController}.php`, change:

```php
use App\Attribute\ReleaseSessionEarly;
```

to:

```php
use Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly;
```

The `#[ReleaseSessionEarly]` usages themselves are unchanged.

- [ ] **Step 8: Delete the local attribute and listener**

```bash
git rm src/Attribute/ReleaseSessionEarly.php src/EventListener/ReleaseSessionEarlyListener.php
```

If `src/Attribute/` is now empty, `git rm` removes the directory automatically.

- [ ] **Step 9: Verify no references remain and the listener is still wired**

```bash
grep -rn 'App\\Attribute\\ReleaseSessionEarly' src/ ; echo "(should be empty)"
docker compose -p seo_cms -f ./docker/compose.yaml exec app \
  /app/bin/console debug:event-dispatcher kernel.controller
```

Expected: the grep prints nothing, and the dispatcher listing includes `Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener`. If it is absent, the bundle's `config/services.yaml` entry from Task 2 Step 6 is missing or the container cache is stale — run `bin/console cache:clear` and re-check.

- [ ] **Step 10: Commit the attribute move**

```bash
git add -A src/Controller/Admin/Dashboard
git commit -m "refactor(session): use the bundle's ReleaseSessionEarly attribute"
```

- [ ] **Step 11: Repoint the four import controllers at the bundle's form**

In each of `src/Controller/Admin/{HostImportController,SiteImportController,ProxyImportController,CloudflareAccountImportController}.php`, change:

```php
use App\Form\BulkImportType;
use App\Form\Entity\BulkImport;
```

to:

```php
use Playtini\EasyAdminHelperBundle\Form\BulkImportType;
use Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport;
```

Some controllers import only one of the two — change whichever are present. The `BulkImportType::class`, `BulkImport::MODE_*` and `getRows()` usages in the method bodies are unchanged.

- [ ] **Step 12: Delete the local form classes**

```bash
git rm src/Form/BulkImportType.php src/Form/Entity/BulkImport.php
```

- [ ] **Step 13: Verify no references remain**

```bash
grep -rn 'App\\Form\\BulkImportType\|App\\Form\\Entity\\BulkImport' src/ templates/ ; echo "(should be empty)"
```

Expected: no output.

- [ ] **Step 14: Exercise one import page end to end**

The form type is now resolved from the bundle rather than the app, so confirm Symfony can still build it.

Open `http://localhost.net:8000`, go to the Host import page, paste a two-line TSV whose header matches the importer's expected columns, submit in `Create and skip existing` mode, and confirm the result message appears without an exception. Repeat for one other import page.

- [ ] **Step 15: Run the tests and static analysis**

```bash
make test
make analyze
```

Expected: both pass, with the same counts as before the change.

- [ ] **Step 16: Commit the form move**

```bash
git add -A src/Controller/Admin src/Form
git commit -m "refactor(form): use the bundle's BulkImport form and DTO"
```

- [ ] **Step 17: Open the PR**

```bash
git push -u origin feat/adopt-bundle-extraction
gh pr create --fill
```

---

## Done when

- Bundle 1.34 is tagged, pushed, and resolvable from satis.
- kobzar's `AuditLog` is 15 lines and its schema dump is byte-identical before and after.
- seo-cms's `AuditLog` is 14 lines, its four ex-local classes are deleted, its eight controllers point at the bundle, and its schema dump is byte-identical before and after.
- Both fork PRs are open with green tests and static analysis.
