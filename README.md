EasyAdmin Helper Bundle
=======================

Install
-------

Add the library

    composer require playtini/easy-admin-helper-bundle

Create `App\EasyAdmin\EasyAdminMenu` implementing `EasyAdminMenuInterface`.
Create `DashboardController` implementing `DashboardControllerInterface`.
Add to `config/services.yaml`

```yaml
    Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminMenuInterface:
        class: 'App\EasyAdmin\EasyAdminMenu'
    Playtini\EasyAdminHelperBundle\Dashboard\EasyAdminContext:
        bind:
            $dashboardController: '@App\Controller\Admin\DashboardController'
    Playtini\EasyAdminHelperBundle\Event\DashboardExceptionSubscriber:
        tags: [ { name: kernel.event_subscriber } ]
```

This ensures Symfony autowires and autoconfigures your subscriber.
```

Load routes. Add to `config/routes/easyadmin.yaml`

```yaml
easy_admin_helper:
    resource: '@EasyAdminHelperBundle/config/routes.yaml'
```
