---
title: "Getting started"
description: "First standalone usage of codemonster-ru/validation"
order: 1
---

# Getting started

`codemonster-ru/validation` validates arrays with rule strings and returns a
validation result object.

## Basic usage

Create a validator with the input data and the rules that each field must
satisfy.

```php
use Codemonster\Validation\Validator;

$result = (new Validator())->validate([
    'email' => 'hello@example.com',
], [
    'email' => 'required|email',
]);

if ($result->fails()) {
    $errors = $result->errors();
}
```

Use `validateOrFail()` when invalid data should throw a validation exception.
