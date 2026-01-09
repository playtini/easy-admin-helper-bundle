# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony bundle (`playtini/easy-admin-helper-bundle`) that extends EasyAdmin with helper traits, fields, and controllers for building admin panels. It requires PHP 8.4 and Symfony 7.4+.

## Commands

```bash
# Run all tests
./vendor/bin/phpunit

# Run a specific test
./vendor/bin/phpunit tests/Formatter/AdminFormatterTest.php

# Static analysis (uses phpstan.dist.neon)
./vendor/bin/phpstan

# Install dependencies
composer install
```

## Architecture

### Controller Traits (src/Controller/Traits/)
Composable traits for CRUD controllers:
- `CrudControllerTrait` - Base trait with standard CRUD configuration (date formats, pagination, actions)
- `ArchiveCrudControllerTrait` - Soft-delete via archiving (filters out archived by default)
- `DuplicateCrudControllerTrait` - Entity duplication functionality
- `ReadOnlyCrudControllerTrait` - Disables edit/delete actions
- `InlineEditCrudControllerTrait` - Inline editing support
- `SaveCrudControllerTrait` - Custom save logic
- `UserCrudControllerTrait` - User-related CRUD helpers
- `DashboardTrait` / `DashboardCustomConstructorTrait` - Dashboard controller helpers

### Entity Traits (src/Entity/Traits/)
Doctrine entity traits for common fields:
- `IdEntityTrait` - Auto-increment ID
- `NameEntityTrait` / `NameUniqueEntityTrait` - Name field
- `ArchivableEntityTrait` - Soft-delete with `archivedAt` timestamp
- `CreatableEntityTrait` - `createdAt` timestamp
- `IsEnabledTrait` - Boolean enabled flag
- `UidEntityTrait` - UID field
- `CommentEntityTrait` / `ShortCommentEntityTrait` - Comment fields
- `VirtualFieldsEntityTrait` - For computed/virtual fields

### CrudField Helper (src/Field/CrudField.php)
Factory class for creating pre-configured EasyAdmin fields with consistent styling. Uses static `$disabled` flag for read-only mode. Key methods:
- Layout: `::panel()` - Fieldset panels with Bootstrap column support
- Text: `::text()`, `::textarea()`, `::name()`, `::comment()`
- Numbers: `::id()`, `::int()`
- Dates: `::createdAt()`, `::updatedAt()`, `::archivedAtDate()`, `::dateMinutes()`
- Relations: `::association()` (with autocomplete), `::choices()`
- Special: `::yaml()`, `::uid()`, `::isEnabled()`, `::isLive()`
- Virtual: `::virtual()`, `::virtualInt()`, `::virtualRight()` - For computed display-only fields

### Dashboard
- `CustomDashboardController` - Base dashboard extending EasyAdmin's AbstractDashboardController
- `EasyAdminMenuInterface` - Interface for menu configuration (implement `getTitle()` and `configureMenuItems()`)
- `DashboardHelper` / `EasyAdminContext` - Dashboard utilities

### Doc Controllers (src/Controller/Doc/)
Built-in documentation viewer for markdown files in `{project}/doc/`:
- `DocController` - Lists all docs at `/admin/doc`
- `DocItemController` - Shows single doc at `/admin/doc/{name}`
- `DocDbController` / `DocDiagramController` - Database and diagram documentation
- Supports YAML front matter in markdown files via `FrontmatterParser`

### AdminFormatter (src/Formatter/)
Utility for formatting values in admin views: `formatBoolNull()`, `formatUrlPath()`, `formatHttpStatus()`, `percents()`, `formatExpireDate()`

## Template Override

Bundle templates can be overridden by placing files in `templates/bundles/EasyAdminHelperBundle/`
