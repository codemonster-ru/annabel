---
title: "Error handling"
description: "HTTP exceptions and production error responses"
order: 9
---

# Error handling

Annabel renders exceptions into HTTP responses and reports unhandled HTTP
exceptions through the configured logger.

## HTTP exceptions

Framework HTTP exceptions live under `Codemonster\Annabel\Http\Exceptions`.
They expose stable status and header contracts for bad requests,
authentication, authorization, missing routes, and unsupported methods.

```php
use Codemonster\Annabel\Http\Exceptions\NotFoundHttpException;

throw new NotFoundHttpException('Post not found.');
```

For simple abort-style flows, use `abort()`:

```php
abort(404, 'Post not found.');
```

## Debug mode

Disable debug mode in production:

```dotenv
APP_DEBUG=false
```

Production error responses should not expose internal details.

## Error views

The exception handler looks for debug, status-specific, and generic error
templates. Applications can provide views such as:

- `errors.debug`
- `errors.404`
- `errors.500`
- `errors.generic`

When no template is available, Annabel falls back to a plain text response.

## JSON errors

For requests that expect JSON, HTTP exceptions are rendered as JSON error
responses with the matching status code.
