<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

use Psr\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    private \DateTimeZone $timezone;

    public function __construct(string|\DateTimeZone $timezone = 'UTC')
    {
        $this->timezone = self::resolveTimezone($timezone);
    }

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }

    private static function resolveTimezone(string|\DateTimeZone $timezone): \DateTimeZone
    {
        if ($timezone instanceof \DateTimeZone) {
            return $timezone;
        }

        try {
            return new \DateTimeZone($timezone);
        } catch (\Exception $exception) {
            throw new InvalidDateTimeException('Invalid timezone [' . $timezone . '].', previous: $exception);
        }
    }
}
