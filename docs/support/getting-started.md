---
title: "Getting started"
description: "First standalone usage of codemonster-ru/support"
order: 1
---

# Getting started

`codemonster-ru/support` contains shared helper functions and support contracts
used across Codemonster PHP packages.

## Basic usage

Install this package when another standalone Codemonster package expects shared
helpers or support contracts.

```php
use Codemonster\Support\Contracts\HttpStatusExceptionInterface;
```

The package is intentionally small. Application-level behavior belongs in the
framework or the feature package that owns it.
