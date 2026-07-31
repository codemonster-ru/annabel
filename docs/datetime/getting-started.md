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

## Calendar operations

All operations return new values. Days, weeks, months, quarters, and years are
calendar units; hours, minutes, and seconds are elapsed time.

This distinction is intentional around daylight-saving transitions: adding a
day preserves the local wall-clock time, while adding 24 hours advances the
instant by exactly 86,400 seconds.

```php
$quarter = DateTime::parse('2026-07-31 12:30', 'Europe/Paris')
    ->startOfQuarter();

$nextWeek = $quarter->addWeeks(1);
$changed = $nextWeek->setDate(2026, 8, 15)->setTime(9, 30);
```

Weeks start on Monday by default. Pass an ISO-8601 weekday from `1` (Monday) to
`7` (Sunday) to `startOfWeek()` or `endOfWeek()` to use another first day.

Ranges can be inclusive or exclusive:

```php
$inside = $date->isBetween($start, $end);
$strictlyInside = $date->isBetween($start, $end, inclusive: false);
$earliest = $date->min($other);
$latest = $date->max($other);
```

## Native PHP interoperability

Use timestamps or native immutable values at package boundaries:

```php
$date = DateTime::fromTimestamp($timestamp, 'Europe/Paris');
$sameDate = DateTime::fromInterface($nativeDateTime);

$timestamp = $date->timestamp();
$nativeDateTime = $date->toNative();
```

## Localized formatting

`LocalizedFormatter` delegates patterns, month names, and date/time styles to
ICU. It throws `DateTimeFormattingException` if the requested locale data is
not installed instead of silently using another language.

```php
use Codemonster\DateTime\LocalizedFormatter;

$formatter = new LocalizedFormatter(
    'ru_RU',
    \IntlDateFormatter::NONE,
    \IntlDateFormatter::NONE,
    'd MMMM y, HH:mm',
);

echo $formatter->format($date);
```

English and Russian relative intervals use ICU plural rules:

```php
use Codemonster\DateTime\HumanDiffFormatter;

$human = new HumanDiffFormatter('ru_RU');

echo $human->formatRelative($now, $event); // через 2 дня
```

## Business calendars

Business calendars use ISO-8601 weekday numbers and explicit `Y-m-d` holidays.
The default weekend is Saturday and Sunday.

```php
use Codemonster\DateTime\BusinessCalendar;

$calendar = new BusinessCalendar(
    weekendDays: [6, 7],
    holidays: ['2026-01-01', '2026-01-07'],
);

$nextBusinessDate = $calendar->addBusinessDays($date, 3);
$days = $calendar->businessDaysBetween($start, $end);
```

`businessDaysBetween()` counts both range boundaries when they are business
days. Reversed ranges are rejected.

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

## Annabel integration

Annabel binds `Psr\Clock\ClockInterface` and the `clock` container alias to a
singleton `SystemClock`. The system clock uses UTC; application or user
timezones should be applied when parsing input or formatting output.

```php
use Codemonster\DateTime\DateTime;
use Psr\Clock\ClockInterface;

final class ReportController
{
    public function __construct(private ClockInterface $clock)
    {
    }

    public function generatedAt(): string
    {
        return DateTime::now($this->clock, 'Europe/Paris')->toIso8601String();
    }
}
```
