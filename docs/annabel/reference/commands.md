---
title: "Commands"
description: "Command reference for the Annabel CLI"
order: 1
---

# Commands

Run commands through the framework binary:

```bash
php vendor/bin/annabel <command>
```

The skeleton also exposes common commands through Composer scripts.

## Application

These commands inspect the application and support local development.

| Command | Purpose |
| --- | --- |
| `about` | Show framework information, base path, and loaded providers. |
| `serve [host:port]` | Start PHP's built-in development server. |
| `help [command]` | Show command help. |
| `list` | List available commands. |

## Configuration and routes

Use these commands to inspect or rebuild framework configuration, routes, and
container state.

| Command | Purpose |
| --- | --- |
| `config:get <key>` | Print a single config value. |
| `config:list` | Print config values with sensitive values redacted. |
| `config:cache` | Build the configuration cache. |
| `config:clear` | Clear the configuration cache. |
| `route:list` | List registered routes. |
| `route:cache` | Build the route cache. |
| `route:clear` | Clear the route cache. |
| `container:list` | Show container bindings and instances. |
| `optimize` | Build production caches. |
| `optimize:clear` | Clear generated caches. |

## Generators

Generators create application classes in the conventional directories.

| Command | Purpose |
| --- | --- |
| `make:controller <name>` | Create a controller under `app/Controllers`. |
| `make:model <name>` | Create a model under `app/Models`. |
| `make:middleware <name>` | Create middleware. |
| `make:request <name>` | Create a request class. |
| `make:policy <name>` | Create an authorization policy. |
| `make:job <name>` | Create a queue job. |

Nested names create nested directories:

```bash
php vendor/bin/annabel make:controller Admin/UserController
```

## Database

The database commands create migration artifacts and manage schema and seed
state.

| Command | Purpose |
| --- | --- |
| `make:migration <name>` | Create a migration file. |
| `migrate` | Run pending migrations. |
| `migrate:rollback` | Roll back the latest migration batch. |
| `migrate:status` | Show migration status. |
| `make:seed <name>` | Create a seeder. |
| `seed` | Run seeders. |

## Queues

Use the queue commands to process jobs and manage failed job records.

| Command | Purpose |
| --- | --- |
| `queue:work [queue] [options]` | Process queued jobs. |
| `queue:failed` | List failed jobs. |
| `queue:retry <id-or-all>` | Retry failed jobs. |
| `queue:flush` | Clear failed jobs. |

Worker options include `--once`, `--stop-when-empty`, `--sleep=3`, and
`--max-jobs=0`.

Use finite worker modes in tests and one-off maintenance runs:

```bash
php vendor/bin/annabel queue:work --once
php vendor/bin/annabel queue:work --stop-when-empty
```

## Scheduler

Scheduler commands run due tasks or show the current schedule.

| Command | Purpose |
| --- | --- |
| `schedule:run` | Run due scheduled tasks. |
| `schedule:list` | List scheduled tasks and due/lock state. |

## Publishing

Publishing commands copy package resources into the application.

| Command | Purpose |
| --- | --- |
| `vendor:publish --provider="Provider\\Class"` | Publish by provider. |
| `vendor:publish --tag=config` | Publish resources by tag. |
| `vendor:publish --all` | Publish all registered resources. |
| `vendor:publish --all --force` | Publish all and overwrite files. |

Publishing is explicit and does not overwrite existing files unless `--force`
is passed.
