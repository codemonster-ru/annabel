---
title: "Getting started"
description: "First standalone usage of codemonster-ru/dumper"
order: 1
---

# Getting started

`codemonster-ru/dumper` provides `dump` and `dd` style debugging output for CLI
and web environments.

## Basic usage

Use the dump helpers to inspect values while developing an application.

```php
use Codemonster\Dumper\Dumper;

Dumper::dump($value);

Dumper::dd($request, $user);
```

`dump()` prints values and continues execution. `dd()` dumps values and stops
the process.
