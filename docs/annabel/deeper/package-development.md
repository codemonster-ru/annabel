---
title: "Package development"
description: "Integrating packages with Annabel applications"
order: 9
---

# Package development

Annabel packages should expose normal Composer libraries. Framework integration
belongs in a service provider.

## Package boundary

Keep runtime package behavior framework-agnostic when possible. Use Annabel
only for the integration layer:

- container bindings
- service providers
- publishable resources
- commands
- framework-specific helpers

## Provider discovery

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

Only declare providers owned by the package. Applications can opt out of
package discovery from `config/app.php`.

## Service provider

```php
use Codemonster\Annabel\Providers\ServiceProvider;

final class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Client::class, function () {
            return new Client(config('package.endpoint'));
        });

        $this->commands([
            SyncPackageCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/package.php' => base_path('config/package.php'),
        ], ['config', 'package']);
    }
}
```

## Publishable resources

```php
$this->publishes([
    __DIR__ . '/../config/package.php' => base_path('config/package.php'),
], ['config', 'package']);
```

Publishing is explicit and does not overwrite existing files unless `--force`
is used.

Publish directories for migrations, views, or assets:

```php
$this->publishes([
    __DIR__ . '/../database/migrations' => base_path('database/migrations'),
], ['migrations', 'package-migrations']);
```

## Commands

Register container-resolved commands from the provider:

```php
$this->commands([
    SyncPackageCommand::class,
]);
```

Commands are resolved through the container, so constructor dependencies may be
type-hinted.

## Discovery opt-outs

Applications can disable package discovery globally or by package:

```php
'providers' => [
    'packages' => [
        'discover' => true,
        'dont_discover' => [
            'vendor/package',
        ],
    ],
],
```

Use `'*'` in `dont_discover` to disable all package discovery.

## Release discipline

Package integration APIs become public maintenance surface. Keep provider
metadata, commands, config keys, and publish tags small and stable.
