# Extract Reusable Patterns into easy-admin-helper-bundle

## Context

Analyzed 4 projects that use this bundle. Found significant code duplication — identical CSS, repeated entity traits, repeated controller patterns, and similar CrudField extensions. Extracted these into the bundle to eliminate copy-paste and simplify future projects.

---

## 1. Common CSS inlined into bundle layout

**File**: `templates/layout.html.twig`

Added `<style>` block with classes duplicated across all 3 production projects:
- `.positive` / `.negative` — green/red bold text for values
- `.invisible-report` — hidden text for copy-paste
- `.uid` — monospace 10px truncated display for UIDs
- `.short-1` through `.short-6` — truncation at 50–300px
- `.loading` — opacity 0.5
- `.btn-action` — 60px fixed-width action buttons
- `.btn-xs` / `.btn-group-xs` — extra-small Bootstrap buttons
- `.top-border` — golden border for table row separators
- `.thead-light` — custom table header styling
- `.text-muted-2` / `.text-muted-3` — lighter muted text variants (+ dark mode)
- `.tooltip-inner` — left-aligned pre-formatted tooltips
- `.break-words` — word-break utility

Also added Bootstrap tooltips auto-init:
```js
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
```

**Projects can now delete** these rules from their `public/css/app.css`.

---

## 2. UpdatableEntityTrait

**File**: `src/Entity/Traits/UpdatableEntityTrait.php`

Auto-sets `updatedAt` on PrePersist and PreUpdate via `Dat::now()`. Nullable `DATETIME_IMMUTABLE` column. Follows same pattern as `CreatableEntityTrait`.

---

## 3. DataEntityTrait

**File**: `src/Entity/Traits/DataEntityTrait.php`

JSON `data` column (default `'{}'`). Includes `getDataString(int $maxLength = 200)` that returns YAML-dumped, truncated, HTML-escaped string for admin display.

---

## 4. LongNameEntityTrait

**File**: `src/Entity/Traits/LongNameEntityTrait.php`

Same as `NameEntityTrait` but with `#[ORM\Column(length: 1000)]`. Uses `IdEntityTrait`, caps via `mb_substr`, implements `__toString()`.

---

## 5. New CrudField methods

**File**: `src/Field/CrudField.php`

| Method                             | Returns        | Notes                                         |
|------------------------------------|----------------|-----------------------------------------------|
| `bool($property, $label?, $cols?)` | `BooleanField` | Uses bool-null template, respects `$disabled` |
| `ip($cols?)`                       | `TextField`    | Disabled, small font, label "IP"              |
| `email($cols?)`                    | `EmailField`   | Respects `$disabled`                          |
| `domain($cols?)`                   | `TextField`    | Respects `$disabled`                          |
| `url($property?, $label?, $cols?)` | `UrlField`     | Auto-humanizes label from property            |
| `country($cols?)`                  | `CountryField` | Respects `$disabled`                          |
| `status($cols?)`                   | `TextField`    | Always disabled                               |

New imports added: `CountryField`, `EmailField`, `UrlField`.

---

## 6. AdminFormatter badge methods

**File**: `src/Formatter/AdminFormatter.php`

```php
badge(string $text, string $bg = '#6c757d', string $fg = '#fff'): string
```
Returns inline-styled `<span>` with border-radius, padding, bold text.

```php
badgeMap(string $value, array $colors, string $defaultBg = '#6c757d'): string
```
Looks up `$colors[$value]['bg']` and `['fg']`, falls back to defaults.

---

## 7. QuickDateFilterCrudTrait + template

**Files**:
- `src/Controller/Traits/QuickDateFilterCrudTrait.php`
- `templates/crud/quick_date_filter_index.html.twig`

**Query param**: `?period=24h`

**Trait methods**:
- `applyQuickDateFilter(QueryBuilder $qb)` — reads `period` query param, applies WHERE on date field
- `getQuickDateField()` — override to change field (default: `createdAt`)
- `getQuickDateDefault()` — override to change default (default: `24h`)
- `getQuickDateOptions()` — override to change dropdown options (default: 1h, today, 24h, 7d, 30d)

**Template**: Bootstrap dropdown in `global_actions` block. Uses `ea_url().set('period', key)` for links.

**Usage**:
```php
use QuickDateFilterCrudTrait;

public function configureCrud(Crud $crud): Crud {
    return $this->_configureCrud($crud)
        ->overrideTemplate('crud/index', '@EasyAdminHelper/crud/quick_date_filter_index.html.twig');
}

public function createIndexQueryBuilder(...): QueryBuilder {
    $qb = parent::createIndexQueryBuilder(...);
    return $this->applyQuickDateFilter($qb);
}
```

---

## 8. DateRangePickerCrudTrait + template

**Files**:
- `src/Controller/Traits/DateRangePickerCrudTrait.php`
- `templates/crud/date_range_picker_index.html.twig`

**Query params**: `?from=YYYY-MM-DD&to=YYYY-MM-DD`

**Trait methods**:
- `configureResponseParameters()` — passes `from`/`to` to template
- `createIndexQueryBuilder()` — applies date range filter (default: last 7 days if no filter)
- `applyDateRangeFilter(QueryBuilder $qb, ?SearchDto $searchDto)` — reusable filtering logic
- `getDateRangeField()` — override to change field (default: `createdAt`)
- `getDateRangeDefaultDays()` — override default range (default: 7)

**Template**: Calendar picker in `page_actions` block. Auto-submits on date change. Preset ranges: 7d, 30d, 90d, 180d, This Month, Last Month. JS/CSS assets shipped with the bundle (`public/js/`, `public/css/`), loaded via `{{ asset('bundles/easyadminhelper/...') }}`.

**Usage**:
```php
use DateRangePickerCrudTrait;

public function configureCrud(Crud $crud): Crud {
    return $this->_configureCrud($crud)
        ->overrideTemplate('crud/index', '@EasyAdminHelper/crud/date_range_picker_index.html.twig');
}
// createIndexQueryBuilder() and configureResponseParameters() are handled by the trait automatically
```

---

## 9. Bundle ships vendor JS/CSS assets

**Directory**: `public/`

The bundle now ships common vendor libraries in `public/`. After `composer require` + `assets:install` (Symfony runs this automatically), they're available at `/bundles/easyadminhelper/...`.

```
public/
  js/
    jquery.js          — jQuery (was in all projects)
    jquery.map
    chart.js           — Chart.js 
    moment.min.js      — Moment.js
    vanilla-datetimerange-picker.js
  css/
    vanilla-datetimerange-picker.css
```

**Templates updated** to use `{{ asset('bundles/easyadminhelper/...') }}`:
- `templates/layout.html.twig` — jQuery loaded from bundle
- `templates/crud/date_range_picker_index.html.twig` — moment, datetimerange-picker loaded from bundle

**Projects can now delete** their local copies of these vendor files.

---

## Verification

- **phpstan**: 0 new errors (9 pre-existing in old code, unrelated)
- **phpunit**: 105 tests, 180 assertions — all pass

---

## Files created/modified

| Action   | File                                                 |
|----------|------------------------------------------------------|
| Modified | `templates/layout.html.twig`                         |
| Created  | `src/Entity/Traits/UpdatableEntityTrait.php`         |
| Created  | `src/Entity/Traits/DataEntityTrait.php`              |
| Created  | `src/Entity/Traits/LongNameEntityTrait.php`          |
| Modified | `src/Field/CrudField.php`                            |
| Modified | `src/Formatter/AdminFormatter.php`                   |
| Created  | `src/Controller/Traits/QuickDateFilterCrudTrait.php` |
| Created  | `templates/crud/quick_date_filter_index.html.twig`   |
| Created  | `src/Controller/Traits/DateRangePickerCrudTrait.php` |
| Modified | `templates/crud/date_range_picker_index.html.twig`   |
| Created  | `public/js/jquery.js` + `.map`                       |
| Created  | `public/js/chart.js`                                 |
| Created  | `public/js/moment.min.js`                            |
| Created  | `public/js/vanilla-datetimerange-picker.js`          |
| Created  | `public/css/vanilla-datetimerange-picker.css`        |
