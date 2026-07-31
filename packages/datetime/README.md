# codemonster-ru/datetime

> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the [Annabel monorepo](https://github.com/codemonster-ru/annabel).
>
> Issues and pull requests should be opened there.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/datetime.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/datetime)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/datetime.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/datetime)
[![License](https://img.shields.io/packagist/l/codemonster-ru/datetime.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/datetime)

Immutable date-time operations, timezone conversion, strict parsing, ICU
formatting, business calendars, and PSR-20 clocks for PHP applications.

## Requirements

- PHP 8.2 or newer
- `psr/clock` 1.0
- `ext-intl` with the required ICU locale data for localized and
  human-readable formatting

## Installation

```bash
composer require codemonster-ru/datetime:^1.0
```

## Quick start

```php
use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\FrozenClock;

$clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 05:30:00 UTC'));

$publishedAt = DateTime::now($clock, 'Asia/Novokuznetsk')
    ->addDays(2)
    ->subtractHours(3)
    ->toTimezone('UTC');

echo $publishedAt->toIso8601String();
```

All value operations are immutable. Calendar units such as days and months
preserve local calendar semantics, while hours, minutes, and seconds represent
elapsed time.

## Documentation

Standalone package documentation:
[docs.codemonster.net/datetime](https://docs.codemonster.net/datetime/)

Annabel framework documentation:
[docs.codemonster.net/annabel](https://docs.codemonster.net/annabel/)
