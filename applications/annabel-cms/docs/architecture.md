# Xen Architecture

## Module Lifecycle

Every directory under `app/Modules` must contain a `module.php` manifest.
The module manager performs the following deterministic lifecycle:

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
];
```

Modules may depend on another module's public contracts. They should not depend
on its controllers, persistence models, or concrete services.

## Authentication Boundary

The Auth module publishes `AuthenticatorInterface`, `UserSessionInterface`, and
the immutable `AuthenticatedUser` identity object. Admin depends on those
contracts. The Auth module remains responsible for users, roles, password
verification, and session persistence.

## Frontend Assets

Frontend source and Vite configuration belong to the module. Built assets are
published to the shared `public` directory and resolved through Vite's hashed
manifest. A missing build produces an explicit operational error instead of
PHP warnings or stale filenames.

## Horizontal Scaling

File sessions are suitable for one application node. Multi-node deployments
must use `SESSION_DRIVER=redis` so authentication and CSRF state are shared.
The application remains stateless apart from the configured database and
session backend.

The default file driver stores data in `storage/sessions`. The container
entrypoint creates this directory for the PHP-FPM user. Do not share a global
`/tmp` session directory between root CLI processes and PHP-FPM workers.

Moving a module to an independent service additionally requires a versioned
network API and service discovery. Filesystem modularity alone does not create
a distributed system.
