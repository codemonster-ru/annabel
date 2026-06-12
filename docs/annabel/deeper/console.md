---
title: "Console"
description: "Annabel CLI commands and custom commands"
order: 1
---

# Console

Annabel ships with a lightweight CLI.

## List commands

```bash
php vendor/bin/annabel list
php vendor/bin/annabel help
```

Common commands include `about`, `route:list`, `config:get`, `config:list`,
`container:list`, `vendor:publish`, `serve`, `migrate`, `queue:work`, and
`schedule:run`.

## Application commands

| Command | Purpose |
| --- | --- |
| `about` | Show version, base path, and loaded providers. |
| `serve` | Run PHP's built-in development server. |
| `route:list` | List registered routes. |
| `route:cache` | Build the route cache. |
| `route:clear` | Clear the route cache. |
| `config:get <key>` | Read a config value. |
| `config:list` | List config values with secrets redacted. |
| `config:cache` | Build the config cache. |
| `config:clear` | Clear the config cache. |
| `container:list` | Show container bindings and instances. |
| `vendor:publish` | Publish package resources. |
| `optimize` | Build production caches. |
| `optimize:clear` | Clear generated framework caches. |

## Generators

| Command | Purpose |
| --- | --- |
| `make:controller <name>` | Create a controller. |
| `make:model <name>` | Create a model. |
| `make:middleware <name>` | Create middleware. |
| `make:request <name>` | Create a request class. |
| `make:policy <name>` | Create an authorization policy. |
| `make:job <name>` | Create a queue job. |

Database commands are available when database integration is installed and
configured:

```bash
php vendor/bin/annabel make:migration create_posts_table
php vendor/bin/annabel migrate
php vendor/bin/annabel migrate:rollback
php vendor/bin/annabel migrate:status
php vendor/bin/annabel make:seed UserSeeder
php vendor/bin/annabel seed
```

## Custom command

```php
use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

final class SyncCommand extends Command
{
    public string $name = 'app:sync';
    public string $description = 'Synchronize application data.';

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Synced.');

        return ExitCode::SUCCESS;
    }
}
```

Register commands from a service provider with `$this->commands([...])`.

## Publishing resources

```bash
php vendor/bin/annabel vendor:publish --provider="Vendor\\Package\\Provider"
php vendor/bin/annabel vendor:publish --tag=config
php vendor/bin/annabel vendor:publish --all
php vendor/bin/annabel vendor:publish --all --force
```

Publishing is explicit and does not overwrite existing files unless `--force`
is passed.
