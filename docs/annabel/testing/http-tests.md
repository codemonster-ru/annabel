---
title: "HTTP tests"
description: "Testing requests and responses"
order: 2
---

# HTTP tests

Use `InteractsWithApplication` to issue requests against the application.

```php
$this->get('/')->assertOk();

$this->post('/login', [
    'email' => 'admin@example.com',
    'password' => 'secret',
])->assertRedirect('/dashboard');
```

Assertions are provided by the framework test response wrapper.

## JSON requests

Use the JSON helper to encode request data and set the appropriate request
headers.

```php
$this->json('POST', '/api/users', [
    'email' => 'hello@example.com',
])->assertCreated();
```

JSON requests automatically send `Accept: application/json` and
`Content-Type: application/json`.

## Available assertions

Test responses provide assertions for status codes, headers, content, and JSON
data.

```php
$this->get('/')
    ->assertOk()
    ->assertSee('Welcome')
    ->assertDontSee('Exception');

$this->post('/posts', [])
    ->assertStatus(422)
    ->assertJsonPath('errors.title.0', 'The title field is required.');

$this->get('/login')
    ->assertRedirect('/dashboard');
```

Available response helpers include:

- `status()`
- `content()`
- `json($key = null, $default = null)`
- `baseResponse()`
- `assertStatus()`
- `assertOk()`
- `assertCreated()`
- `assertNoContent()`
- `assertRedirect()`
- `assertHeader()`
- `assertSee()`
- `assertDontSee()`
- `assertJson()`
- `assertJsonPath()`

## Acting as a user

Authenticate the test client when exercising routes protected by auth
middleware.

```php
$this->actingAs($user)
    ->get('/dashboard')
    ->assertOk();
```

The user must implement `Codemonster\Auth\Contracts\AuthenticatableInterface`.
