<?php

declare(strict_types=1);

namespace Codemonster\DateTime\Tests;

use Codemonster\DateTime\BusinessCalendar;
use Codemonster\DateTime\DateTime;
use PHPUnit\Framework\TestCase;

final class BusinessCalendarTest extends TestCase
{
    public function test_it_recognizes_weekends_holidays_and_business_days(): void
    {
        $calendar = new BusinessCalendar([6, 7], ['2026-08-03']);

        self::assertTrue($calendar->isWeekend(DateTime::parse('2026-08-01')));
        self::assertTrue($calendar->isHoliday(DateTime::parse('2026-08-03')));
        self::assertFalse($calendar->isBusinessDay(DateTime::parse('2026-08-03')));
        self::assertTrue($calendar->isBusinessDay(DateTime::parse('2026-08-04')));
    }

    public function test_it_adds_and_subtracts_business_days_without_changing_local_time(): void
    {
        $calendar = new BusinessCalendar([6, 7], ['2026-08-03']);
        $friday = DateTime::parse('2026-07-31 15:30:00', 'Asia/Novokuznetsk');

        self::assertSame(
            '2026-08-04 15:30:00',
            $calendar->addBusinessDays($friday, 1)->format(DateTime::DATABASE_FORMAT),
        );
        self::assertSame(
            '2026-07-30 15:30:00',
            $calendar->addBusinessDays($friday, -1)->format(DateTime::DATABASE_FORMAT),
        );
    }

    public function test_it_counts_business_days_in_an_inclusive_range(): void
    {
        $calendar = new BusinessCalendar([6, 7], ['2026-08-03']);

        self::assertSame(
            2,
            $calendar->businessDaysBetween(
                DateTime::parse('2026-07-31'),
                DateTime::parse('2026-08-04'),
            ),
        );
    }

    public function test_it_supports_non_standard_weekends(): void
    {
        $calendar = new BusinessCalendar([5, 6]);

        self::assertTrue($calendar->isWeekend(DateTime::parse('2026-07-31')));
        self::assertTrue($calendar->isBusinessDay(DateTime::parse('2026-08-02')));
    }
}
