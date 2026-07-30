<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

final class BusinessCalendar
{
    /** @var list<int> */
    private array $weekendDays;

    /** @var array<string, true> */
    private array $holidays = [];

    /**
     * @param list<int> $weekendDays ISO-8601 weekdays from 1 (Monday) to 7 (Sunday)
     * @param iterable<string|DateTime|\DateTimeInterface> $holidays
     */
    public function __construct(array $weekendDays = [6, 7], iterable $holidays = [])
    {
        foreach ($weekendDays as $weekday) {
            if ($weekday < 1 || $weekday > 7) {
                throw new \InvalidArgumentException('Weekend ISO weekdays must be between 1 and 7.');
            }
        }

        $this->weekendDays = array_values(array_unique($weekendDays));

        foreach ($holidays as $holiday) {
            $date = is_string($holiday)
                ? DateTime::fromFormat('Y-m-d', $holiday)
                : self::dateTime($holiday);

            $this->holidays[$date->format('Y-m-d')] = true;
        }
    }

    public function isWeekend(DateTime|\DateTimeInterface $dateTime): bool
    {
        return in_array((int) self::dateTime($dateTime)->format('N'), $this->weekendDays, true);
    }

    public function isHoliday(DateTime|\DateTimeInterface $dateTime): bool
    {
        return isset($this->holidays[self::dateTime($dateTime)->format('Y-m-d')]);
    }

    public function isBusinessDay(DateTime|\DateTimeInterface $dateTime): bool
    {
        return !$this->isWeekend($dateTime) && !$this->isHoliday($dateTime);
    }

    public function addBusinessDays(DateTime|\DateTimeInterface $dateTime, int $days): DateTime
    {
        $dateTime = self::dateTime($dateTime);

        if ($days === 0) {
            return $dateTime;
        }

        $step = $days > 0 ? 1 : -1;
        $remaining = abs($days);

        while ($remaining > 0) {
            $dateTime = $dateTime->addDays($step);

            if ($this->isBusinessDay($dateTime)) {
                $remaining--;
            }
        }

        return $dateTime;
    }

    public function businessDaysBetween(
        DateTime|\DateTimeInterface $start,
        DateTime|\DateTimeInterface $end,
    ): int {
        $date = self::dateTime($start)->startOfDay();
        $end = self::dateTime($end)->startOfDay();

        if ($end->isBefore($date)) {
            throw new \InvalidArgumentException('The end of a business-day range must not precede its start.');
        }

        $days = 0;

        while (!$date->isAfter($end)) {
            if ($this->isBusinessDay($date)) {
                $days++;
            }

            $date = $date->addDays(1);
        }

        return $days;
    }

    private static function dateTime(DateTime|\DateTimeInterface $dateTime): DateTime
    {
        return $dateTime instanceof DateTime ? $dateTime : DateTime::fromInterface($dateTime);
    }
}
