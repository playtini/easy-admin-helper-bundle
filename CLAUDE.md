# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Symfony bundle (`playtini/easy-admin-helper-bundle`) that extends EasyAdmin with helper traits, fields, and controllers for building admin panels. It requires PHP 8.4 and Symfony 7.2.

## Commands

```bash
# Run all tests
./vendor/bin/phpunit

# Run a specific test
./vendor/bin/phpunit tests/Formatter/AdminFormatterTest.php

# Static analysis
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

### Entity Traits (src/Entity/Traits/)
Doctrine entity traits for common fields:
- `IdEntityTrait` - Auto-increment ID
- `NameEntityTrait` / `NameUniqueEntityTrait` - Name field
- `ArchivableEntityTrait` - Soft-delete with `archivedAt` timestamp
- `CreatableEntityTrait` - `createdAt` timestamp
- `IsEnabledTrait` - Boolean enabled flag
- `UidEntityTrait` - UID field
- `CommentEntityTrait` / `ShortCommentEntityTrait` - Comment fields

### CrudField Helper (src/Field/CrudField.php)
Factory class for creating pre-configured EasyAdmin fields with consistent styling:
- `CrudField::id()`, `::name()`, `::text()`, `::textarea()`
- `CrudField::createdAt()`, `::updatedAt()`, `::archivedAtDate()`
- `CrudField::association()`, `::choices()`, `::yaml()`
- `CrudField::virtual()`, `::virtualInt()` - For computed display fields
- `CrudField::isEnabled()`, `::comment()`, `::uid()`

### Dashboard
- `CustomDashboardController` - Base dashboard extending EasyAdmin's AbstractDashboardController
- `EasyAdminMenuInterface` - Interface for menu configuration (implement `getTitle()` and `configureMenuItems()`)
- `DashboardHelper` / `EasyAdminContext` - Dashboard utilities

### Doc Controllers (src/Controller/Doc/)
Built-in documentation viewer for markdown files in `{project}/doc/`:
- Routes at `/admin/doc` and `/admin/doc/{name}`
- Supports YAML front matter in markdown files

### AdminFormatter (src/Formatter/)
Utility for formatting values in admin views: `formatBoolNull()`, `formatUrlPath()`, `formatHttpStatus()`, `percents()`, `formatExpireDate()`

## Template Override

Bundle templates can be overridden by placing files in `templates/bundles/EasyAdminHelperBundle/`
