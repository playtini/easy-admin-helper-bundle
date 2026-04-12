# Upgrade Doc Controllers & Templates

Ported improvements from other project back into the bundle.

## What changed

### `src/Controller/Doc/DocController.php`
- Docs grouped by subdirectory: `$items` flat list replaced with `$groups` keyed by `$file->getRelativePath()` (`/` for root)
- Relative path included in `name` param (e.g. `integrations/slack`)
- `ksort($groups)` for alphabetical group ordering
- Template receives `groups` instead of `items`

### `src/Controller/Doc/DocItemController.php`
- Route now supports nested paths: `requirements: ['name' => '.+']`
- Added `priority: -1` to avoid shadowing `/admin/doc/db` and `/admin/doc/diagram/`

### Templates (`index`, `item`, `db`)
- All three switched from `@EasyAdminHelper/layout.html.twig` to `@EasyAdmin/page/content.html.twig`
- `index.html.twig`: flat list replaced with grouped loop (`for group, items in groups`) with `<h3>` headers; root group labeled "General"
- `item.html.twig` and `db.html.twig`: removed wrapper `<div class="doc ...">`, content rendered directly

### Not changed
- `EasyAdminContext` service kept as-is (bundle pattern for non-dashboard controllers)
- `ea` context variable still passed from controllers (needed by `@EasyAdmin/page/content.html.twig`)
- `DocDbController` and `DocDiagramController` unchanged

## Verification
- `./vendor/bin/phpunit` — 100 tests, 175 assertions, all pass
- `./vendor/bin/phpstan` — no new errors (9 pre-existing errors in entity traits)
