---
title: "Service providers"
description: "Registering services, commands, and publishable resources"
order: 3
---

# Service providers

Service providers are the integration boundary for framework services and
packages.

## Register bindings

Use `register()` to declare container bindings without resolving application
services.

```php
use Codemonster\Annabel\Providers\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, fn () => new Client());
    }
}
```

## Boot integrations

Use `boot()` for work that needs all providers to be registered:

```php
public function boot(): void
{
    $this->publishes([
        __DIR__ . '/../../config/package.php' => base_path(
            'config/package.php',
        ),
    ], ['config']);
}
```

## Commands

Providers may expose package or application commands to the framework CLI.

```php
$this->commands([
    SyncCommand::class,
]);
```

Commands are resolved through the container.

## Package discovery

Packages may declare providers in Composer metadata:

```json
{
  "extra": {
    "annabel": {
      "providers": [
        "Vendor\\Package\\PackageServiceProvider"
      ]
    }
  }
}
```

Applications can disable selected discovered packages in `config/app.php`.
