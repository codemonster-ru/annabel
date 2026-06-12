---
title: "Getting started"
description: "First standalone usage of codemonster-ru/razor"
order: 1
---

# Getting started

`codemonster-ru/razor` is a Razor-like template engine for PHP.

## Basic usage

```php
use Codemonster\Razor\RazorEngine;
use Codemonster\View\Locator\DefaultLocator;

$engine = new RazorEngine(
    new DefaultLocator(__DIR__ . '/views'),
    cachePath: __DIR__ . '/storage/views'
);

echo $engine->render('home', ['name' => 'Ada']);
```
