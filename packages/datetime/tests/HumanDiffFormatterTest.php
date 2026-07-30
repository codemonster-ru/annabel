<?php

declare(strict_types=1);

namespace Codemonster\DateTime\Tests;

use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\HumanDiffFormatter;
use PHPUnit\Framework\TestCase;

final class HumanDiffFormatterTest extends TestCase
{
    public function test_it_formats_english_relative_intervals(): void
    {
        $formatter = new HumanDiffFormatter('en_US');
        $reference = DateTime::parse('2026-07-31 12:00:00');

        self::assertSame(
            'in 2 days 3 hours',
            $formatter->formatRelative($reference, $reference->addDays(2)->addHours(3), 2),
        );
        self::assertSame(
            '5 minutes ago',
            $formatter->formatRelative($reference, $reference->subtractMinutes(5)),
        );
        self::assertSame('just now', $formatter->formatRelative($reference, $reference));
    }

    public function test_it_formats_russian_plural_forms(): void
    {
        $formatter = new HumanDiffFormatter('ru_RU');
        $reference = DateTime::parse('2026-07-31 12:00:00');

        self::assertSame(
            'через 2 дня',
            $formatter->formatRelative($reference, $reference->addDays(2)),
        );
        self::assertSame(
            '5 минут назад',
            $formatter->formatRelative($reference, $reference->subtractMinutes(5)),
        );
        self::assertSame('только что', $formatter->formatRelative($reference, $reference));
    }

    public function test_it_rejects_unsupported_locales(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new HumanDiffFormatter('de_DE');
    }
}
