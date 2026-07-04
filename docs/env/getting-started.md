---
title: "Getting started"
description: "First standalone usage of codemonster-ru/env"
order: 1
---

# Getting started

`codemonster-ru/env` loads `.env` files and reads environment values with
optional casting.

## Basic usage

Load environment variables before reading them through the package API.

```php
use Codemonster\Env\Env;

Env::safeLoad(__DIR__ . '/.env');

$debug = Env::getCast('APP_DEBUG', false, true);
$database = Env::get('DB_DATABASE');
```

Use `safeLoad()` when the file may be missing. Use `load()` when missing or
invalid files should fail the boot process.

## Writing values

Use `Env::write()` when an installer or setup flow needs to persist environment
values into an existing `.env` file.

```php
use Codemonster\Env\Env;

Env::write(__DIR__ . '/.env', [
    'APP_NAME' => 'Annabel CMS',
    'DB_HOST' => '127.0.0.1',
]);
```

Existing keys are replaced in place. New keys are appended. Comments, blank
lines, and the file line ending are preserved.
