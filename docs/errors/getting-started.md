---
title: "Getting started"
description: "First standalone usage of codemonster-ru/errors"
order: 1
---

# Getting started

`codemonster-ru/errors` provides exception handler contracts and a smart
exception handler for HTTP and CLI applications.

## Basic usage

```php
use Codemonster\Errors\Handlers\SmartExceptionHandler;

$handler = new SmartExceptionHandler(debug: true);

$response = $handler->handle($exception);
```

The handler can render debug output in development and safer responses in
production.
