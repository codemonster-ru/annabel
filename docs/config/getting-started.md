---
title: "Getting started"
description: "First standalone usage of codemonster-ru/config"
order: 1
---

# Getting started

`codemonster-ru/config` loads PHP configuration files and exposes values through
dot notation.

## Basic usage

```php
use Codemonster\Config\Config;

Config::load(__DIR__ . '/config');

$timezone = Config::get('app.timezone', 'UTC');

Config::set('app.debug', false);
```

Configuration files should return arrays. A file named `config/app.php` becomes
the `app` configuration namespace.
