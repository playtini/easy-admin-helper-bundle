# Bundle Extraction Design

**Date:** 2026-07-27
**Repository:** `playtini/easy-admin-helper-bundle`
**Adopting projects:** `playtini/kobzar`, `playtini/seo-cms`

## Goal

Move code that has independently converged across service forks out of the forks
and into the bundle, then prove the move by adopting it in two of them.

## Background

Services are created by forking `playtini/symfony-template`. A fork is a
one-time copy: once made, it never receives template changes again. Code that
every fork needs therefore gets written once per fork, and drifts. The bundle is
the only channel that reaches an existing service, so shared code belongs there.

The preceding work (`symfony-template` PR #13, bundle 1.33) removed the
*template's* copies of code the bundle already owned. This spec addresses the
opposite direction: code that lives only in the forks and should move up.

## Scope

Three pieces of code move, grouped into two components. Each was found in two or
more forks in substantially the same form, except where noted.

| Component | Found in | Moves to |
| --- | --- | --- |
| `AuditLog` entity shape | kobzar, seo-cms | `Entity\BaseAuditLog` (MappedSuperclass) |
| `ReleaseSessionEarly` attribute + listener | seo-cms, tds | `Attribute\`, `EventListener\` |
| `BulkImportType` + `BulkImport` DTO | seo-cms | `Form\`, `Form\Dto\` |

`BulkImport` appears in only one fork today. It is included because it is a
generic TSV-paste import form with no seo-cms domain knowledge in it, and
because the import *controllers* that do carry domain knowledge stay behind — so
the extracted part is exactly the reusable part.

---

## Component A — `BaseAuditLog`

### Current state

`kobzar/src/Entity/AuditLog.php` and `seo-cms/src/Entity/AuditLog.php` are
byte-identical except that kobzar additionally does `use VirtualFieldsEntityTrait;`.
Both declare the same eight columns, the same three indexes, the same
`HasLifecycleCallbacks`, and the same eight-argument `create()` factory.

### Design

Add `Playtini\EasyAdminHelperBundle\Entity\BaseAuditLog`, an
`#[ORM\MappedSuperclass] abstract class` carrying:

- `username` — `#[ORM\Column(length: 255)] string`, default `''`
- `routeName` — `#[ORM\Column(length: 255)] string`, default `''`
- `routeParams` — `#[ORM\Column(type: Types::JSON, nullable: true)] ?array`
- `url` — `#[ORM\Column(length: 2048)] string`, default `''`
- `httpMethod` — `#[ORM\Column(length: 10)] string`, default `''`
- `statusCode` — `#[ORM\Column(type: Types::SMALLINT)] int`, default `0`
- `ip` — `#[ORM\Column(length: 45)] string`, default `''`
- `responseTimeMs` — `#[ORM\Column] int`, default `0`

plus the eight getters and the `create()` factory.

**Use `CreatableEntityTrait` only — not `IdEntityTrait` as well.** The bundle's
`CreatableEntityTrait` already does `use IdEntityTrait;`, so it supplies `$id`
and `getId()`. Composing both into the same class collides on `getId()`.

**`create()` returns `static` and calls `new static()`.** For PHPStan (the
bundle runs level 5, which reports unsafe `new static()`), `BaseAuditLog`
declares `final public function __construct() {}`. A final constructor is safe
on a Doctrine entity: hydration uses `newInstanceWithoutConstructor()` and never
calls it.

`BaseAuditLog` does **not** use `VirtualFieldsEntityTrait`. kobzar needs it,
seo-cms does not; the concrete class is where that choice belongs.

### What each project keeps

```php
#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['created_at'])]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['route_name'])]
class AuditLog extends BaseAuditLog
{
    use VirtualFieldsEntityTrait; // kobzar only
}
```

Indexes, the repository binding, and `HasLifecycleCallbacks` stay on the
concrete class. `HasLifecycleCallbacks` is not inherited from a
MappedSuperclass in a way that can be relied on, and the indexes are a physical
choice each project should own.

### Why MappedSuperclass and not a concrete vendor entity

A concrete entity in the bundle would need every consuming project to add a
Doctrine mapping entry for the bundle's namespace, and would force one table
name and one index set on all of them. A MappedSuperclass parent is resolved
through the class hierarchy by autoload — no mapping configuration is added to
any project — and it lets kobzar keep `VirtualFieldsEntityTrait` while seo-cms
does not. This matches the two existing precedents in the org:
`task-bundle`'s `BaseTask` and `gupalo/monolog-dbal-logger`'s `Log`.

### Trait substitution and the DDL

Each project currently uses its own `App\Entity\Traits\IdTrait` and
`CreatableEntityTrait`. Adoption replaces them with the bundle's. The generated
DDL must not change:

- `IdTrait` writes `#[ORM\Column(type: 'integer')]`; the bundle's
  `IdEntityTrait` writes bare `#[ORM\Column]` on a `?int`, which Doctrine infers
  as `integer`. Same column.
- The fork's `CreatableEntityTrait` declares `\DateTimeInterface $createdAt`
  (non-nullable, uninitialized); the bundle's declares
  `?DateTimeInterface $createdAt = null`. Doctrine does not infer `nullable`
  from the PHP type — it defaults to `nullable: false` in both. Same column.
- The fork's `IdTrait` and `CreatableEntityTrait` carry
  `#[Groups(['id:Read'])]` / `#[Groups(['created:Read'])]`. These are Serializer
  metadata with no schema effect. A grep of kobzar's `src/` and `config/` finds
  no consumer of either group, so dropping them is inert. The same grep must be
  run against seo-cms before its adoption; if a consumer exists, the concrete
  class re-declares the group rather than the bundle carrying it.

The schema-diff gate in Section E is what proves all of this rather than
assuming it.

### What stays behind

`AuditLogListener` stays in each project. The two copies diverge by ~95 lines —
which routes to skip, which users to exclude, how the username is resolved.
That is policy, not shape. Extracting it would mean extracting a configuration
surface large enough to be worse than the duplication.

---

## Component B — `ReleaseSessionEarly` and `BulkImport`

### B.1 `ReleaseSessionEarly`

seo-cms and tds both have `App\Attribute\ReleaseSessionEarly` and
`App\EventListener\ReleaseSessionEarlyListener`. The listener bodies are
identical. tds's versions are the superset: the attribute carries a docblock
explaining the concurrency motivation, and the listener is `final readonly` with
imported `ReflectionClass`/`ReflectionMethod` rather than FQNs.

Move tds's versions to
`Playtini\EasyAdminHelperBundle\Attribute\ReleaseSessionEarly` and
`Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener`,
rewriting the docblock's `{@see \App\EventListener\...}` reference to the
bundle's namespace and dropping the tds-specific `lock_mode: 0` sentence (that
is a tds configuration fact, not a bundle fact).

`config/services.yaml` currently registers only `Dashboard\`, `Frontmatter\`,
and `Controller\`. Add an entry for the listener with `autoconfigure: true` so
the `#[AsEventListener]` attribute is honoured:

```yaml
    Playtini\EasyAdminHelperBundle\EventListener\ReleaseSessionEarlyListener:
        autoconfigure: true
```

The listener is inert in a project that uses the attribute nowhere: it returns
at the `controllerHasAttribute()` check.

### B.2 `BulkImport`

Move `seo-cms/src/Form/BulkImportType.php` to
`Playtini\EasyAdminHelperBundle\Form\BulkImportType` and
`seo-cms/src/Form/Entity/BulkImport.php` to
`Playtini\EasyAdminHelperBundle\Form\Dto\BulkImport`.

`Form\Dto\`, not `Form\Entity\` — a namespace called `Entity` next to the
bundle's Doctrine `Entity\` namespace invites confusion in exactly the place
this spec is adding a Doctrine MappedSuperclass.

Two deliberate deviations from a literal copy:

1. **`private string $data;` becomes `private string $data = '';`.** As written,
   `getData()` on a freshly constructed `BulkImport` throws
   `Error: must not be accessed before initialization`. The form flow always
   sets it first, so initializing is behaviour-preserving there and removes the
   trap for any other caller.
2. **The empty `__construct()` on both classes is deleted.** It does nothing.

Everything else — the five mode constants, `getRows()`, `normalizeKeys()`, the
form's textarea/choice/submit layout — is copied verbatim.

`getRows()` uses `Luchaninov\CsvFileLoader\TsvStringLoader`, so the bundle's
`composer.json` gains `"luchaninov/csv-file-loader": "^1.10"` (the constraint
seo-cms already uses).

The bulk-import *controllers* stay in seo-cms. They know which entity they build,
which columns map to which fields, and what each mode means for that entity.

---

## Considered and rejected — `CrudField` promotion

An earlier draft of this design included a third component: reconcile the forks'
`CrudField` subclasses against the bundle base, deleting overrides that
duplicate it and promoting the audit-log field helpers.

Checked against the pilot pair, it does not survive:

- kobzar's `CrudField` overrides exactly two base methods, `name()` and `ip()`,
  and both are genuine divergences. The base `name()` is 12 columns and applies
  `setDisabled(self::$disabled)`/`setRequired()`; kobzar's is 3 columns and
  plain. The base `ip()` is 12 columns, disabled, and wraps the value in
  `<span class="small">`; kobzar's is 2 columns and plain. Neither is redundant.
- Of the audit-log helpers present in both forks, only `routeName()` and
  `httpMethod()` are the same beyond the `$cols` default. `auditUrl()`,
  `routeParams()`, `responseTimeMs()`, and `username()` each differ in
  substance — kobzar's `routeParams()` binds a virtual field and renders
  `key: value` pairs `onlyOnDetail()`, seo-cms's binds the real property and
  JSON-encodes it. `statusCodeBadge()` exists only in kobzar.
- Promoting the two convergent methods would make the forks inherit the bundle's
  uniform `$cols = 12` default in place of kobzar's deliberate 3 and 1, changing
  the admin layout. That is a regression bought with two five-line methods.

The `CrudField` growth story may still hold for other forks — atlas was the
original motivation — but it is not part of this spec, and it is not what the
kobzar/seo-cms pilot demonstrates.

---

## Adoption

### D.1 kobzar

1. `src/Entity/AuditLog.php` — `extends BaseAuditLog`; delete the eight
   properties, eight getters, and `create()`; keep `use VirtualFieldsEntityTrait;`
   (the bundle's), the entity/index/lifecycle attributes.
2. Switch the `use` statements from `App\Entity\Traits\` to the bundle's, or
   delete them where `BaseAuditLog` now supplies the member.
3. Delete `src/Entity/Traits/IdTrait.php` and
   `src/Entity/Traits/CreatableEntityTrait.php` **only if** no other entity uses
   them; otherwise leave them and let `AuditLog` alone stop using them.

kobzar exercises Component A. It has no `ReleaseSessionEarly` and no
`BulkImport`.

### D.2 seo-cms

1. The same `AuditLog` change, without `VirtualFieldsEntityTrait`.
2. Delete `src/Attribute/ReleaseSessionEarly.php` and
   `src/EventListener/ReleaseSessionEarlyListener.php`; repoint every
   `use App\Attribute\ReleaseSessionEarly;` at the bundle's.
3. Delete `src/Form/BulkImportType.php` and `src/Form/Entity/BulkImport.php`;
   repoint the import controllers' `use` statements at the bundle's.

seo-cms exercises all three pieces.

tds keeps its own `ReleaseSessionEarly` for now. Backporting tds and atlas is
follow-on work, not part of this spec.

---

## Verification

The acceptance gate for each adoption, in order:

```bash
bin/console doctrine:schema:update --dump-sql   # must emit NO statements
vendor/bin/phpunit
vendor/bin/phpstan analyze
```

The schema dump is the load-bearing check. Empty output proves the
MappedSuperclass generates identical DDL to the inlined properties, which means
adoption needs no migration and touches no data.

**A non-empty diff is a stop, not a migration.** It means `BaseAuditLog` does
not match what the fork actually has, and the fix belongs in the bundle. Writing
a migration to reconcile the difference would push the bundle's shape onto
production tables under the guise of a refactor.

Bundle-side tests are unit-level:

- `BaseAuditLog` — `create()` populates all eight fields and returns the
  concrete subclass type, not the base; getters round-trip. Tested through a
  throwaway concrete subclass in `tests/`.
- `ReleaseSessionEarlyListener` — saves a started session on an attributed
  controller; does nothing for a sub-request, an unattributed controller, a
  request with no session, or an unstarted session.
- `BulkImport` — `getRows()` parses TSV, lowercases and trims header keys, skips
  blank rows; `getData()` on a fresh instance returns `''` rather than throwing.
- `BulkImportType` — `configureOptions()` sets `data_class`; the built form has
  `data`, `mode`, and `save` children and the five mode choices.

No Doctrine integration harness is added to the bundle. The forks' own suites
plus the schema-diff gate are the integration proof.

---

## Sequencing

1. **Bundle** — add all three pieces with tests, release **1.34**.
2. **kobzar** — adopt, verify, PR.
3. **seo-cms** — adopt, verify, PR.

Steps 2 and 3 cannot start until 1.34 is installable from satis. The cron
deployed on `tools.s.p777.org` rebuilds the index every 15 minutes, so that wait
is bounded.

Each adoption is its own PR in its own repository. Neither adoption blocks the
other.

---

## Out of scope

- `LogEntry` — it is a subclass of Gedmo's `AbstractLogEntry`, already vendor
  code. There is nothing to extract.
- `AuditLogListener` — see Component A.
- The bulk-import controllers — see Component B.2.
- `CrudField` promotion — see "Considered and rejected".
- atlas and tds adoption.
- Migrating the forks off `App\Entity\Traits\` wholesale. This spec moves
  `AuditLog` only; the other entities in each fork are a separate job.
