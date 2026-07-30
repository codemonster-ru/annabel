<?php

declare(strict_types=1);

namespace Codemonster\DateTime\Tests;

use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\FrozenClock;
use Codemonster\DateTime\SystemClock;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class ClockTest extends TestCase
{
    public function test_system_clock_uses_its_configured_timezone(): void
    {
        $clock = new SystemClock('Asia/Novokuznetsk');

        self::assertInstanceOf(ClockInterface::class, $clock);
        self::assertSame('Asia/Novokuznetsk', $clock->now()->getTimezone()->getName());
    }

    public function test_frozen_clock_always_returns_the_same_instant(): void
    {
        $clock = new FrozenClock(DateTime::parse('2026-07-31 12:30:00'));

        self::assertSame($clock->now(), $clock->now());
        self::assertSame('2026-07-31T12:30:00+00:00', $clock->now()->format(\DateTimeInterface::ATOM));
    }

    public function test_frozen_clock_can_produce_advanced_and_rewound_copies(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:00 UTC'));
        $advanced = $clock->advance(new \DateInterval('PT2H'));
        $rewound = $clock->rewind(new \DateInterval('P1D'));

        self::assertSame('2026-07-31 12:30:00', $clock->now()->format(DateTime::DATABASE_FORMAT));
        self::assertSame('2026-07-31 14:30:00', $advanced->now()->format(DateTime::DATABASE_FORMAT));
        self::assertSame('2026-07-30 12:30:00', $rewound->now()->format(DateTime::DATABASE_FORMAT));
    }
}
