<?php

declare(strict_types=1);

namespace Codemonster\DateTime\Tests;

use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\FrozenClock;
use Codemonster\DateTime\InvalidDateTimeException;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    public function test_it_parses_and_converts_timezones_without_changing_the_instant(): void
    {
        $local = DateTime::parse('2026-07-31 12:30:00', 'Asia/Novokuznetsk');
        $utc = $local->toTimezone('UTC');

        self::assertSame('2026-07-31 05:30:00', $utc->format(DateTime::DATABASE_FORMAT));
        self::assertSame('UTC', $utc->timezoneName());
        self::assertTrue($local->isSameInstant($utc));
    }

    public function test_from_format_is_strict_and_resets_unspecified_fields(): void
    {
        $date = DateTime::fromFormat('Y-m-d', '2024-02-29');

        self::assertSame('2024-02-29 00:00:00', $date->format(DateTime::DATABASE_FORMAT));

        $this->expectException(InvalidDateTimeException::class);

        DateTime::fromFormat('Y-m-d', '2023-02-29');
    }

    public function test_parse_rejects_invalid_calendar_dates(): void
    {
        $this->expectException(InvalidDateTimeException::class);

        DateTime::parse('2026-02-31');
    }

    public function test_it_creates_a_date_from_a_timestamp_in_the_requested_timezone(): void
    {
        $date = DateTime::fromTimestamp(0, 'Asia/Novokuznetsk');

        self::assertSame('1970-01-01 07:00:00', $date->format(DateTime::DATABASE_FORMAT));
    }

    public function test_named_arithmetic_is_immutable(): void
    {
        $original = DateTime::parse('2026-07-31 12:30:45');
        $changed = $original
            ->addDays(2)
            ->subtractHours(3)
            ->addMinutes(15)
            ->subtractSeconds(5);

        self::assertSame('2026-07-31 12:30:45', $original->format(DateTime::DATABASE_FORMAT));
        self::assertSame('2026-08-02 09:45:40', $changed->format(DateTime::DATABASE_FORMAT));
    }

    public function test_it_supports_week_and_quarter_arithmetic(): void
    {
        $date = DateTime::parse('2026-02-15 12:30:00');

        self::assertSame('2026-03-01', $date->addWeeks(2)->format('Y-m-d'));
        self::assertSame('2026-02-01', $date->subtractWeeks(2)->format('Y-m-d'));
        self::assertSame('2026-08-15', $date->addQuarters(2)->format('Y-m-d'));
        self::assertSame('2025-11-15', $date->subtractQuarters(1)->format('Y-m-d'));
    }

    public function test_days_are_calendar_units_while_hours_are_elapsed_time(): void
    {
        $date = DateTime::parse('2026-03-28 12:00:00', 'Europe/Berlin');

        self::assertSame(
            '2026-03-29 12:00:00 +02:00',
            $date->addDays(1)->format('Y-m-d H:i:s P'),
        );
        self::assertSame(
            '2026-03-29 13:00:00 +02:00',
            $date->addHours(24)->format('Y-m-d H:i:s P'),
        );
    }

    public function test_it_supports_native_date_intervals(): void
    {
        $date = DateTime::parse('2026-01-15 10:00:00');

        self::assertSame(
            '2027-03-15 10:00:00',
            $date->add(new \DateInterval('P1Y2M'))->format(DateTime::DATABASE_FORMAT),
        );
        self::assertSame(
            '2025-11-15 10:00:00',
            $date->subtract(new \DateInterval('P2M'))->format(DateTime::DATABASE_FORMAT),
        );
    }

    public function test_it_calculates_calendar_boundaries(): void
    {
        $date = DateTime::parse('2024-02-15 12:30:45.123456');

        self::assertSame('2024-02-01 00:00:00.000000', $date->startOfMonth()->format('Y-m-d H:i:s.u'));
        self::assertSame('2024-02-29 23:59:59.999999', $date->endOfMonth()->format('Y-m-d H:i:s.u'));
        self::assertSame('2024-01-01 00:00:00.000000', $date->startOfYear()->format('Y-m-d H:i:s.u'));
        self::assertSame('2024-12-31 23:59:59.999999', $date->endOfYear()->format('Y-m-d H:i:s.u'));
    }

    public function test_it_calculates_week_and_quarter_boundaries(): void
    {
        $date = DateTime::parse('2026-07-31 12:30:45');

        self::assertSame('2026-07-27 00:00:00', $date->startOfWeek()->format(DateTime::DATABASE_FORMAT));
        self::assertSame('2026-08-02 23:59:59.999999', $date->endOfWeek()->format('Y-m-d H:i:s.u'));
        self::assertSame('2026-07-26', $date->startOfWeek(7)->format('Y-m-d'));
        self::assertSame(3, $date->quarter());
        self::assertSame('2026-07-01 00:00:00', $date->startOfQuarter()->format(DateTime::DATABASE_FORMAT));
        self::assertSame('2026-09-30 23:59:59.999999', $date->endOfQuarter()->format('Y-m-d H:i:s.u'));
    }

    public function test_setters_are_strict_and_immutable(): void
    {
        $original = DateTime::parse('2026-07-31 12:30:45.123456');
        $changed = $original->setDate(2024, 2, 29)->setTime(23, 59, 58, 654321);

        self::assertSame('2026-07-31 12:30:45.123456', $original->format('Y-m-d H:i:s.u'));
        self::assertSame('2024-02-29 23:59:58.654321', $changed->format('Y-m-d H:i:s.u'));

        $this->expectException(InvalidDateTimeException::class);

        $original->setDate(2023, 2, 29);
    }

    public function test_set_time_rejects_out_of_range_components(): void
    {
        $this->expectException(InvalidDateTimeException::class);

        DateTime::parse('2026-07-31')->setTime(24, 0);
    }

    public function test_it_compares_instants_and_calculates_differences(): void
    {
        $earlier = DateTime::parse('2026-07-31 10:00:00');
        $later = DateTime::parse('2026-08-02 12:00:00');

        self::assertTrue($earlier->isBefore($later));
        self::assertTrue($later->isAfter($earlier));
        self::assertSame(2, $earlier->diff($later)->days);
    }

    public function test_it_compares_ranges_and_selects_minimum_and_maximum(): void
    {
        $start = DateTime::parse('2026-07-01');
        $middle = DateTime::parse('2026-07-15');
        $end = DateTime::parse('2026-07-31');

        self::assertTrue($middle->isBetween($start, $end));
        self::assertFalse($start->isBetween($start, $end, false));
        self::assertSame($start, $middle->min($start));
        self::assertSame($end, $middle->max($end));
    }

    public function test_between_rejects_an_inverted_range(): void
    {
        $this->expectException(InvalidDateTimeException::class);

        DateTime::parse('2026-07-15')->isBetween(
            DateTime::parse('2026-07-31'),
            DateTime::parse('2026-07-01'),
        );
    }

    public function test_now_uses_the_injected_clock_and_optional_output_timezone(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 05:30:00 UTC'));

        $date = DateTime::now($clock, 'Asia/Novokuznetsk');

        self::assertSame('2026-07-31 12:30:00', $date->format(DateTime::DATABASE_FORMAT));
    }

    public function test_it_rejects_invalid_timezones(): void
    {
        $this->expectException(InvalidDateTimeException::class);

        DateTime::parse('2026-07-31', 'Not/A_Timezone');
    }
}
