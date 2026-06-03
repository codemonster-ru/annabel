# codemonster-ru/http

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/http.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/http)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/http.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/http)
[![License](https://img.shields.io/packagist/l/codemonster-ru/http.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/http)
[![Tests](https://github.com/codemonster-ru/http/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/http/actions/workflows/tests.yml)

Lightweight object-oriented HTTP foundation for PHP.

## Table of Contents

-   [Installation](#installation)
-   [Usage](#usage)
-   [Design Goals](#design-goals)
-   [API Reference (Highlights)](#api-reference-highlights)
-   [Edge Cases](#edge-cases)
-   [Testing](#testing)
-   [Migration Notes](#migration-notes)
-   [Author](#author)
-   [License](#license)

## Design Goals

-   Simple, small HTTP layer for frameworks or microservices.
-   Predictable, test-friendly API with immutable helpers.
-   Safe defaults for headers, cookies, and proxy handling.

## Installation

```bash
composer require codemonster-ru/http
```

## Usage

### Handling HTTP Requests

```php
use Codemonster\Http\Request;

// Capture the current request from PHP globals
$request = Request::capture();

echo $request->method();      // GET
echo $request->uri();         // /hello
echo $request->query('id');   // 42
echo $request->input('name'); // Vasya
```

You can also create a request manually (useful for tests or CLI):

```php
$request = new Request(
    'POST',
    '/api/user',
    ['page' => 2],
    ['name' => 'John'],
    ['Accept' => 'application/json']
);

if ($request->wantsJson()) {
    // Return JSON
}
```

Request data helpers:

```php
// input() excludes files, files() returns normalized uploads
$name = $request->input('name');
$files = $request->files();

// all() merges query + body + files
$all = $request->all();

// headers/server access
$headers = $request->headers();
$server = $request->server();

// full URL with query string
$url = $request->fullUrlWithQuery();

// immutable request changes
$request2 = $request
    ->withHeader('X-Trace', '1')
    ->withoutHeader('X-Deprecated')
    ->withQuery(['page' => 2])
    ->withInput(['name' => 'Vasya']);

// files with nested keys
$avatarName = $request->files('attachments.docs.a.name');
```

Trusted proxies:

```php
// Trust proxy IPs (exact or CIDR) for ip() and isSecure()
Request::setTrustedProxies(['192.168.1.0/24', '2001:db8::/32']);

$ip = $request->ip();
$secure = $request->isSecure();
$ua = $request->userAgent();
```

Security note:

Only set trusted proxies if you control them. Otherwise, `X-Forwarded-*` headers can be spoofed and `ip()/isSecure()` will be incorrect.

Method override:

```php
// For POST forms, override method via header or _method field
$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'PATCH';
$_POST['_method'] = 'PUT';
```

Middleware-style response sending:

```php
$request = Request::capture();
$response = $next($request);

// Respects HEAD automatically
$response->sendFor($request);
```

### Sending HTTP Responses

```php
use Codemonster\Http\Response;

// Basic response
$response = new Response('Hello world', 200);
$response->send();

// JSON response with custom json_encode flags
return Response::json(['ok' => true], 200, [], JSON_UNESCAPED_SLASHES)->send();

// Redirect
return Response::redirect('/login');

// HEAD response (skip body)
$request = Request::capture();
(new Response('Hello'))->sendFor($request);

// Or send HEAD explicitly
(new Response('Hello'))->sendHead();

// Cookies (immutable)
$response = (new Response('OK'))
    ->withCookie('session', 'abc', ['path' => '/', 'httponly' => true])
    ->send();

// Cookie with options
(new Response('OK'))
    ->cookie('remember', '1', ['max_age' => 3600, 'samesite' => 'lax'])
    ->send();

// Cookies (mutable)
(new Response('OK'))
    ->cookie('session', 'abc')
    ->send();

// Multiple Set-Cookie via header array
(new Response('OK'))
    ->header('Set-Cookie', ['a=1', 'b=2'])
    ->send();

// SameSite=None forces Secure
(new Response('OK'))
    ->cookie('session', 'abc', ['samesite' => 'none'])
    ->send();

// Custom headers
$response = (new Response('Created', 201))
    ->header('X-Custom', '1')
    ->type('text/plain')
    ->send();

// Immutable response changes
$response = (new Response('OK', 200))
    ->withStatus(201)
    ->withHeader('X-Trace', '1')
    ->withType('text/plain')
    ->withHeaders(['X-Foo' => 'bar'])
    ->withoutHeader('X-Deprecated')
    ->withoutHeaders();
```

Convenience helpers:

```php
// Filter input data
$only = $request->only(['name', 'email']);
$except = $request->except(['password']);

// Dot-notation for nested data
$onlyNested = $request->only(['user.name']);
$exceptNested = $request->except(['user.password']);

// Dot-notation for input/query
$name = $request->input('user.name');
$page = $request->query('meta.page');

// Dot-notation for files
$fileName = $request->files('attachments.docs.a.name');
```

## API Reference (Highlights)

Request:

-   `Request::capture()`
-   `method()`, `uri()`, `fullUrl()`, `fullUrlWithQuery()`
-   `query()`, `input()`, `files()`, `all()`, `body()`
-   `headers()`, `server()`, `header()`
-   `only()`, `except()` (dot-notation supported)
-   `ip()`, `isSecure()`, `userAgent()`
-   `withHeader()`, `withoutHeader()`, `withQuery()`, `withInput()`
-   `setTrustedProxies()` / `getTrustedProxies()`

Response:

-   `send()`, `sendFor()`, `sendHead()`
-   `json()`, `redirect()`, `empty()`, `type()`
-   `header()`, `withHeader()`, `withHeaders()`, `withoutHeader()`, `withoutHeaders()`
-   `withStatus()`, `withType()`
-   `withCookie()`, `withoutCookie()`, `cookie()`

## Edge Cases

```php
// 204/304 responses never send a body
(new Response('Will not be sent', 204))->send();

// If headers were already sent, an exception is thrown
// (guarded by headers_sent()).
```

## Testing

You can run tests with the command:

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/http/blob/main/LICENSE)
