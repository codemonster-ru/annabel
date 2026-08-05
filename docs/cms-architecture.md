# Annabel CMS Architecture

## Module Lifecycle

Every directory under `applications/annabel-cms/app/Modules` must contain a
`module.php` manifest. The module manager performs the following deterministic
lifecycle:

1. Discover and validate manifests.
2. Remove explicitly disabled modules.
3. Resolve and sort dependencies.
4. Register view namespaces.
5. Register service providers.
6. Register routes.
7. Boot service providers.

A minimal manifest:

```php
<?php

return [
    'name' => 'Example',
    'version' => '1.0.0',
    'dependencies' => ['Core'],
    'provider' => ExampleModuleServiceProvider::class,
    'routes' => 'routes/web.php',
    'views' => 'views',
    'assets' => [],
    'permissions' => [
        [
            'code' => 'example.manage',
            'name' => 'Manage examples',
            'category' => 'Examples',
            'sort_order' => 100,
        ],
    ],
];
```

Modules may depend on another module's public contracts. They should not depend
on its controllers, persistence models, or concrete services.

## Authentication and Authorization Boundary

The Auth module publishes `AuthenticatorInterface`, `UserSessionInterface`,
`AuthorizationInterface`, and the immutable `AuthenticatedUser` identity
object. Admin depends on those contracts. The Auth module remains responsible
for users, roles, permission assignments, password verification, and session
persistence.

Permission definitions belong to the module that owns the protected operation
and are declared in its manifest. Role assignments store the stable permission
code, so installing a module does not require editing or synchronizing a
central Auth catalog.

Use `AuthorizationInterface::allows($user, $ability, $subject)` for both global
and object-level checks. A module that owns an object policy registers it from
its service provider; controllers and middleware must not invoke policies or
inspect roles directly. The `admin` role is the system-wide superuser bypass.

## Frontend Assets

Frontend source and Vite configuration belong to the module. Built assets are
published to the shared `public` directory and resolved through Vite's hashed
manifest. A missing build produces an explicit operational error instead of PHP
warnings or stale filenames.

## Horizontal Scaling

File sessions are suitable for one application node. Multi-node deployments
must use `SESSION_DRIVER=redis` so authentication and CSRF state are shared. The
application remains stateless apart from the configured database and session
backend.

The default file driver stores data in `storage/sessions`. The container
entrypoint creates this directory for the PHP-FPM user. Do not share a global
`/tmp` session directory between root CLI processes and PHP-FPM workers.

Moving a module to an independent service additionally requires a versioned
network API and service discovery. Filesystem modularity alone does not create a
distributed system.
