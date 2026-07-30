---
title: "Getting started"
description: "First standalone usage of codemonster-ru/datetime"
order: 1
---

# Getting started

`codemonster-ru/datetime` provides immutable date-time operations, explicit
timezone conversion, and testable PSR-20 clocks.

## Parsing and formatting

Dates are parsed in UTC unless a timezone is supplied explicitly.

```php
use Codemonster\DateTime\DateTime;

$publishedAt = DateTime::parse('2026-07-31 12:30', 'Asia/Novokuznetsk')
    ->addDays(2)
    ->subtractHours(3)
    ->toTimezone('UTC');

echo $publishedAt->format(DateTime::DATABASE_FORMAT);
```

Use `fromFormat()` when input must match a known format. Unspecified date and
time fields are reset rather than filled from the current system time.

```php
$date = DateTime::fromFormat('Y-m-d', '2026-07-31');
```

## Testable time

`DateTime::now()` accepts any PSR-20 clock.

```php
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\FrozenClock;

$clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 05:30:00 UTC'));
$now = DateTime::now($clock, 'Asia/Novokuznetsk');
```

`FrozenClock::advance()` and `FrozenClock::rewind()` return new clock instances;
the original clock remains unchanged.
