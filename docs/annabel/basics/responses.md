---
title: "Responses"
description: "Returning HTML, JSON, redirects, and response objects"
order: 5
---

# Responses

Route handlers may return response objects or values Annabel can convert into
responses.

## HTML

Return an HTML response when sending rendered markup directly.

```php
return response()->html('<h1>Hello</h1>');
```

## JSON

Return a JSON response for API and asynchronous clients.

```php
return json(['ok' => true]);
```

## Redirects

Return a redirect response to send the client to another URL.

```php
return response()->redirect('/dashboard');
```

## Views

Render a view when the response body comes from a template.

```php
return view('home', [
    'title' => 'Welcome',
]);
```
