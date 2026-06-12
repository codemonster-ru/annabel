---
title: "Getting started"
description: "First standalone usage of codemonster-ru/ssr-bridge"
order: 1
---

# Getting started

`codemonster-ru/ssr-bridge` calls a Node SSR service or local SSR process from
PHP and returns rendered HTML.

## Basic usage

Connect the bridge to an SSR process and use it to render component payloads.

```php
use Codemonster\Ssr\SsrBridge;

$bridge = new SsrBridge([
    'transport' => 'http',
    'url' => 'http://127.0.0.1:13714/render',
]);

$html = $bridge->render('Home', ['name' => 'Ada']);
```
