---
title: "HTTP client"
description: "Calling external HTTP APIs"
order: 5
---

# HTTP client

Annabel exposes a lightweight HTTP client through `http_client()`.

## Usage

Resolve the configured client through the helper for application HTTP requests.

```php
$response = http_client()
    ->baseUrl('https://api.example.com')
    ->acceptJson()
    ->get('/users/1');

$user = $response->throw()->json();
```

The default transport uses PHP streams. Custom transports are useful for tests
or advanced integrations.

## Requests

Use the convenience methods for common verbs or build a request with explicit
options.

```php
$client = http_client()
    ->baseUrl('https://api.example.com')
    ->timeout(10)
    ->withHeader('X-Client', 'annabel')
    ->acceptJson();

$users = $client->get('/users', ['active' => 1]);
$created = $client->post('/users', ['email' => 'hello@example.com']);
$updated = $client->put('/users/1', ['name' => 'Annabel']);
$patched = $client->patch('/users/1', ['name' => 'Annabel']);
$deleted = $client->delete('/users/1');
```

Array request bodies are JSON-encoded and receive `Content-Type:
application/json` unless a content type header is already set.

## Responses

Inspect the response status, headers, raw body, or decoded JSON data.

```php
$response = http_client()->get('https://api.example.com/users/1');

$status = $response->status();
$body = $response->body();
$headers = $response->headers();
$contentType = $response->header('content-type');

if ($response->successful()) {
    $data = $response->json();
}

$response->throw();
```

`throw()` raises an HTTP client exception for responses with status `400` or
higher.

## Custom transport

Inject a custom transport when you need deterministic tests:

```php
$client = new Codemonster\HttpClient\HttpClient(new FakeTransport());
```

The transport must implement
`Codemonster\HttpClient\Contracts\TransportInterface`.
