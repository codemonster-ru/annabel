---
title: "Getting started"
description: "First standalone usage of codemonster-ru/view-php"
order: 1
---

# Getting started

`codemonster-ru/view-php` is a PHP template engine for `codemonster-ru/view`.

## Basic usage

```php
use Codemonster\View\Locator\DefaultLocator;
use Codemonster\View\Engines\PhpEngine;

$engine = new PhpEngine(new DefaultLocator(__DIR__ . '/views'));

echo $engine->render('home', ['name' => 'Ada']);
```

Template names are resolved by the locator against configured view paths.
