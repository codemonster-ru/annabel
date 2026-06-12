---
title: "Getting started"
description: "First standalone usage of codemonster-ru/view"
order: 1
---

# Getting started

`codemonster-ru/view` provides the view manager, engine contract, and locator
contract used by concrete view engines.

## Basic usage

Configure the view manager, then render templates with the data they need.

```php
use Codemonster\View\View;

$view = new View([
    'php' => $phpEngine,
]);

echo $view->render('home', ['name' => 'Ada']);
```

Install a concrete engine such as `codemonster-ru/view-php` or
`codemonster-ru/view-ssr` to render templates.
