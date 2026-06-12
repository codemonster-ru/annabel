---
title: "Responses"
description: "Returning HTML, JSON, redirects, and response objects"
order: 5
---

# Responses

Route handlers may return response objects or values Annabel can convert into
responses.

## HTML

```php
return response()->html('<h1>Hello</h1>');
```

## JSON

```php
return json(['ok' => true]);
```

## Redirects

```php
return response()->redirect('/dashboard');
```

## Views

```php
return view('home', [
    'title' => 'Welcome',
]);
```
