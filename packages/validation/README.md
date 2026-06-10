# Codemonster Validation

Small validation primitives for Annabel applications.

## Usage

```php
use Codemonster\Validation\Validator;

$validator = new Validator();

$result = $validator->validate([
    'email' => 'hello@example.com',
], [
    'email' => 'required|email',
]);

if ($result->fails()) {
    $errors = $result->errors();
}
```

The validator supports scalar rules, nested fields through dot notation,
validated data, `validateOrFail()`, and custom rules through `extend()`.
