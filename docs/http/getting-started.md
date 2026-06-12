---
title: "Getting started"
description: "First standalone usage of codemonster-ru/http"
order: 1
---

# Getting started

`codemonster-ru/http` provides request, response, URI, and stream objects for
HTTP applications.

## Basic usage

```php
use Codemonster\Http\Request;
use Codemonster\Http\Response;

$request = Request::capture();

return Response::json([
    'path' => $request->getUri()->getPath(),
]);
```

Responses can be sent directly:

```php
Response::redirect('/login')->send();
```
