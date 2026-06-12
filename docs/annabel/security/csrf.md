---
title: "CSRF protection"
description: "Protecting state-changing web requests"
order: 3
---

# CSRF protection

CSRF protection verifies that state-changing web requests include a valid token.

## Form token

```php
<?= csrf_field() ?>
```

## Configuration

Configure CSRF behavior in `config/security.php`.

Common options include enabling the middleware, adding it to the kernel,
verifying JSON requests, and choosing the input key.

```php
'csrf' => [
    'enabled' => true,
    'add_to_kernel' => true,
    'verify_json' => false,
    'input_key' => '_token',
    'except_methods' => ['GET', 'HEAD', 'OPTIONS'],
    'except' => ['api/*'],
],
```

`except_methods` are treated as safe methods and are not verified. Use `except`
for URI patterns that should bypass CSRF verification.
