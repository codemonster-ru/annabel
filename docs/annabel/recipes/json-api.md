---
title: "Build a JSON API endpoint"
description: "Create a validated JSON endpoint"
order: 3
---

# Build a JSON API endpoint

This recipe creates a small JSON endpoint with validation and throttling.

## Route

```php
use App\Controllers\ApiUserController;

router()->post('/api/users', [ApiUserController::class, 'store'])
    ->middleware('throttle:api');
```

## Controller

```php
namespace App\Controllers;

use App\Models\User;
use Codemonster\Annabel\Http\ValidatesRequests;
use Codemonster\Http\Request;

final class ApiUserController
{
    use ValidatesRequests;

    public function store(Request $request): mixed
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
            'name' => 'required|string|max:255',
        ]);

        $user = User::create($data);

        return json([
            'data' => $user->toArray(),
        ], 201);
    }
}
```

Validation failures for JSON requests return JSON `422` responses.

## Test

```php
$this->json('POST', '/api/users', [
    'email' => 'hello@example.com',
    'name' => 'Annabel',
])->assertCreated()
  ->assertJsonPath('data.email', 'hello@example.com');
```
