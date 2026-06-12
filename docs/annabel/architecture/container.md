---
title: "Service container"
description: "Dependency injection and service resolution"
order: 2
---

# Service container

The Annabel application is also the service container and implements
`Psr\Container\ContainerInterface`.

## Bind services

```php
app()->bind(Service::class, fn () => new Service());

app()->singleton(Client::class, fn () => new Client());
```

## Resolve services

```php
$service = app(Service::class);
```

Named constructor parameters may be passed during resolution:

```php
$user = app(User::class, ['name' => 'Annabel']);
```

Passing parameters to an already resolved singleton throws an exception.

## Constructor injection

Controllers, commands, and container-resolved services may type-hint
dependencies. The container resolves them recursively when possible.
