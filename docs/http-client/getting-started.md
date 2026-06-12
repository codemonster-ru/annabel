---
title: "Getting started"
description: "First standalone usage of codemonster-ru/http-client"
order: 1
---

# Getting started

`codemonster-ru/http-client` provides a small immutable HTTP client with a
stream transport and response helpers.

## Basic usage

Create a client and use its request methods to retrieve or send HTTP data.

```php
use Codemonster\HttpClient\HttpClient;

$response = (new HttpClient())
    ->baseUrl('https://api.example.com')
    ->acceptJson()
    ->get('/users/1');

$user = $response->throw()->json();
```
