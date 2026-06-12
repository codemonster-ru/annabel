---
title: "Getting started"
description: "First standalone usage of codemonster-ru/view-ssr"
order: 1
---

# Getting started

`codemonster-ru/view-ssr` is an SSR engine for `codemonster-ru/view`. It
delegates rendering to `codemonster-ru/ssr-bridge`.

## Basic usage

Configure the SSR renderer and pass component data when rendering a response.

```php
use Codemonster\Ssr\SsrBridge;
use Codemonster\View\Engines\SsrEngine;

$engine = new SsrEngine(new SsrBridge([
    'transport' => 'http',
    'url' => 'http://127.0.0.1:13714/render',
]));

echo $engine->render('Home', ['name' => 'Ada']);
```
