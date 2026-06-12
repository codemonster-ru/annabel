---
title: "Validation"
description: "Validating request and configuration data"
order: 7
---

# Validation

Annabel includes a small validation layer for request and configuration data.

## Validate data

Create a validator from input data and the rules each field must satisfy.

```php
$result = validator([
    'email' => 'hello@example.com',
], [
    'email' => 'required|email',
]);

if ($result->fails()) {
    $errors = $result->errors();
}
```

## Controller validation

Use the controller trait to validate request data and return standard failure
responses.

```php
use Codemonster\Annabel\Http\ValidatesRequests;
use Codemonster\Http\Request;

final class RegisterController
{
    use ValidatesRequests;

    public function store(Request $request): mixed
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
        ]);

        // ...
    }
}
```

Validation failures return JSON `422` responses for API requests or redirect
back with flashed errors for web forms.

## Available rules

Rules can be combined to express presence, type, format, and size constraints.

| Rule | Behavior |
| --- | --- |
| `required` | Value must be present and not empty. |
| `nullable` | Allows `null` or empty string and skips other rules. |
| `string` | Value must be a string. |
| `integer` | Value must pass integer validation. |
| `numeric` | Value must be numeric. |
| `boolean` | Value must be `true`, `false`, `0`, `1`, `"0"`, or `"1"`. |
| `array` | Value must be an array. |
| `email` | Value must be a valid email address. |
| `url` | Value must be a valid URL. |
| `confirmed` | Value must match `<field>_confirmation`. |
| `same:field` | Value must match another field. |
| `in:a,b` | Value must be one of the listed scalar values. |
| `min:n` | Minimum number, string length, or array count. |
| `max:n` | Maximum number, string length, or array count. |

## Nested fields

Rules may use dot notation:

```php
$result = validator([
    'user' => ['email' => 'hello@example.com'],
], [
    'user.email' => 'required|email',
]);
```

## Validated data

Read only fields that passed validation before persisting or processing input.

```php
$data = validator($input, [
    'email' => 'required|email',
])->validated();
```

Use `validateOrFail()` when an exception-driven flow is clearer:

```php
$data = validator()->validateOrFail($input, [
    'email' => 'required|email',
]);
```

## Custom rules

Register a custom rule when validation cannot be expressed by the built-in set.

```php
validator()->extend('lowercase', function (
    string $field,
    mixed $value,
): ?string {
    return is_string($value) && $value === strtolower($value)
        ? null
        : "The {$field} field must be lowercase.";
});
```

Custom validators return `null` for success or an error message for failure.
