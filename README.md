EasyAdmin Helper Bundle
=======================

Extends EasyAdmin with reusable traits, fields, and controllers for building admin panels. Requires PHP 8.4+ and Symfony 7.4+/8.0+.

Install
-------

    composer require playtini/easy-admin-helper-bundle

Create `App\EasyAdmin\EasyAdminMenu` implementing `EasyAdminMenuInterface`.
Create `DashboardController` extending `CustomDashboardController`.

Add to `config/services.yaml`:

```yaml
    Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminMenuInterface:
        class: 'App\EasyAdmin\EasyAdminMenu'
    Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext:
        bind:
            $dashboardController: '@App\Controller\Admin\DashboardController'
    Playtini\EasyAdminHelperBundle\Event\DashboardExceptionSubscriber:
        tags: [ { name: kernel.event_subscriber } ]
```

Load routes. Add to `config/routes/easyadmin.yaml`:

```yaml
easy_admin_helper:
    resource: '@EasyAdminHelperBundle/config/routes.yaml'
```

Install bundle assets (symlinks JS/CSS to `public/bundles/easyadminhelper/`):

    php bin/console assets:install

Features
--------

### Controller Traits

| Trait                           | Purpose                                                                      |
|---------------------------------|------------------------------------------------------------------------------|
| `CrudControllerTrait`           | Base CRUD config (date formats, pagination, actions, detail→edit redirect)   |
| `ArchiveCrudControllerTrait`    | Soft-delete via archiving (auto-filters archived, archive/unarchive actions) |
| `DuplicateCrudControllerTrait`  | Entity duplication via `DuplicateInterface`                                  |
| `ReadOnlyCrudControllerTrait`   | Disables all edit/delete actions, makes fields disabled                      |
| `InlineEditCrudControllerTrait` | Inline editing support                                                       |
| `SaveCrudControllerTrait`       | Custom save logic via repository                                             |
| `UserCrudControllerTrait`       | Current user helpers                                                         |
| `QuickDateFilterCrudTrait`      | Dropdown with preset date periods (1h, today, 24h, 7d, 30d)                  |
| `DateRangePickerCrudTrait`      | Calendar-based date range picker with `from`/`to` params                     |

### Entity Traits

| Trait                                            | Fields                                        |
|--------------------------------------------------|-----------------------------------------------|
| `IdEntityTrait`                                  | Auto-increment `id`                           |
| `NameEntityTrait` / `NameUniqueEntityTrait`      | `name` (varchar 255)                          |
| `LongNameEntityTrait`                            | `name` (varchar 1000)                         |
| `CreatableEntityTrait`                           | `createdAt` (auto-set on persist)             |
| `UpdatableEntityTrait`                           | `updatedAt` (auto-set on persist/update)      |
| `ArchivableEntityTrait`                          | `archivedAt` (soft-delete timestamp)          |
| `IsEnabledTrait`                                 | `isEnabled` (boolean)                         |
| `UidEntityTrait`                                 | `uid` (unique string)                         |
| `CommentEntityTrait` / `ShortCommentEntityTrait` | `comment` (text / varchar 1024)               |
| `DataEntityTrait`                                | `data` (JSON column with YAML display helper) |
| `VirtualFieldsEntityTrait`                       | Stub methods for computed fields              |

### CrudField Helper

Static factory for pre-configured EasyAdmin fields:

```php
yield CrudField::id();
yield CrudField::panel('Details', 6);
yield CrudField::name();
yield CrudField::email();
yield CrudField::domain();
yield CrudField::ip();
yield CrudField::country();
yield CrudField::url('websiteUrl');
yield CrudField::status();
yield CrudField::bool('isVerified', 'Verified');
yield CrudField::createdAt();
yield CrudField::updatedAt();
yield CrudField::yaml('config', isIndex: true);
yield CrudField::virtual('Score', fn($v, $entity) => $entity->getScore());
```

### AdminFormatter

```php
AdminFormatter::badge('active', '#198754')           // colored badge
AdminFormatter::badgeMap('error', $colorMap)          // badge with color lookup
AdminFormatter::formatExpireDate($date)               // color-coded by days remaining
AdminFormatter::formatHttpStatus(200)                 // Bootstrap status badge
AdminFormatter::formatBoolNullEmoji(true)             // 🟢 / 🔴
AdminFormatter::muteZero(0)                           // grayed-out zero
AdminFormatter::percents(25, 100)                     // "25%"
```

### Quick Date Filter

Dropdown with preset date periods. Query param: `?period=24h`

```php
class ClickCrudController extends AbstractCrudController
{
    use CrudControllerTrait;
    use QuickDateFilterCrudTrait;

    public function configureCrud(Crud $crud): Crud {
        return $this->_configureCrud($crud)
            ->overrideTemplate('crud/index', '@EasyAdminHelper/crud/quick_date_filter_index.html.twig');
    }

    public function createIndexQueryBuilder(...): QueryBuilder {
        $qb = parent::createIndexQueryBuilder(...);
        return $this->applyQuickDateFilter($qb);
    }
}
```

Override `getQuickDateField()`, `getQuickDateDefault()`, `getQuickDateOptions()` to customize.

### Date Range Picker

Calendar-based date range picker. Query params: `?from=2025-01-01&to=2025-01-31`

```php
class EventCrudController extends AbstractCrudController
{
    use CrudControllerTrait;
    use DateRangePickerCrudTrait;

    public function configureCrud(Crud $crud): Crud {
        return $this->_configureCrud($crud)
            ->overrideTemplate('crud/index', '@EasyAdminHelper/crud/date_range_picker_index.html.twig');
    }
}
```

The trait handles `createIndexQueryBuilder()` and `configureResponseParameters()` automatically. Override `getDateRangeField()` and `getDateRangeDefaultDays()` to customize.

### Doc Controllers

Built-in documentation viewer for markdown files in `{project}/doc/`. Supports YAML front matter. Routes:
- `/admin/doc` — doc index
- `/admin/doc/{name}` — single doc page
- `/admin/doc/db` — database diagrams

### Bundled Assets

The bundle ships common vendor JS/CSS in `public/`. After `assets:install` they're available at `/bundles/easyadminhelper/`:

- `js/jquery.js` — jQuery
- `js/chart.js` — Chart.js
- `js/moment.min.js` — Moment.js
- `js/vanilla-datetimerange-picker.js` — Date range picker
- `css/vanilla-datetimerange-picker.css` — Date range picker styles

The bundle layout (`templates/layout.html.twig`) includes common CSS utilities (`.positive`, `.negative`, `.uid`, `.short-1`–`.short-6`, `.btn-xs`, etc.) and auto-initializes Bootstrap tooltips.
