<?php

declare(strict_types=1);

namespace Codemonster\DateTime\Tests;

use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\DateTimeFormattingException;
use Codemonster\DateTime\LocalizedFormatter;
use PHPUnit\Framework\TestCase;

final class LocalizedFormatterTest extends TestCase
{
    public function test_it_formats_dates_with_an_icu_pattern_and_locale(): void
    {
        $date = DateTime::parse('2026-07-31 12:30:00', 'Europe/Paris');

        $russian = new LocalizedFormatter(
            'ru_RU',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'd MMMM y',
        );
        $english = new LocalizedFormatter(
            'en_US',
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            'MMMM d, y',
        );

        self::assertSame('31 июля 2026', $russian->format($date));
        self::assertSame('July 31, 2026', $english->format($date));
    }

    public function test_it_supports_icu_date_and_time_styles(): void
    {
        $date = DateTime::parse('2026-07-31 12:30:00', 'UTC');
        $formatter = new LocalizedFormatter(
            'en_US',
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
        );

        self::assertStringContainsString('7/31/26', $formatter->format($date));
    }

    public function test_it_rejects_locales_without_icu_data(): void
    {
        $this->expectException(DateTimeFormattingException::class);

        (new LocalizedFormatter('xx_XX'))->format(DateTime::parse('2026-07-31'));
    }
}
