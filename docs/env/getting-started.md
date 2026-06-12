---
title: "Getting started"
description: "First standalone usage of codemonster-ru/env"
order: 1
---

# Getting started

`codemonster-ru/env` loads `.env` files and reads environment values with
optional casting.

## Basic usage

```php
use Codemonster\Env\Env;

Env::safeLoad(__DIR__ . '/.env');

$debug = Env::getCast('APP_DEBUG', false, true);
$database = Env::get('DB_DATABASE');
```

Use `safeLoad()` when the file may be missing. Use `load()` when missing or
invalid files should fail the boot process.
