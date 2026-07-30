<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

use Psr\Clock\ClockInterface;

final class DateTime
{
    public const DATABASE_FORMAT = 'Y-m-d H:i:s';

    private function __construct(private \DateTimeImmutable $dateTime)
    {
    }

    public static function now(
        ?ClockInterface $clock = null,
        string|\DateTimeZone|null $timezone = null,
    ): self {
        $dateTime = ($clock ?? new SystemClock())->now();

        if ($timezone !== null) {
            $dateTime = $dateTime->setTimezone(self::resolveTimezone($timezone));
        }

        return new self($dateTime);
    }

    public static function parse(
        string $value,
        string|\DateTimeZone $timezone = 'UTC',
    ): self {
        try {
            $dateTime = new \DateTimeImmutable($value, self::resolveTimezone($timezone));
        } catch (\Exception $exception) {
            throw new InvalidDateTimeException('Invalid date-time [' . $value . '].', previous: $exception);
        }

        self::guardLastErrors($value);

        return new self($dateTime);
    }

    public static function fromFormat(
        string $format,
        string $value,
        string|\DateTimeZone $timezone = 'UTC',
    ): self {
        $format = str_starts_with($format, '!') ? $format : '!' . $format;
        $dateTime = \DateTimeImmutable::createFromFormat(
            $format,
            $value,
            self::resolveTimezone($timezone),
        );

        if ($dateTime === false) {
            throw new InvalidDateTimeException(
                'Date-time [' . $value . '] does not match format [' . $format . '].',
            );
        }

        self::guardLastErrors($value);

        return new self($dateTime);
    }

    public static function fromTimestamp(
        int $timestamp,
        string|\DateTimeZone $timezone = 'UTC',
    ): self {
        return new self(
            (new \DateTimeImmutable('@' . $timestamp))
                ->setTimezone(self::resolveTimezone($timezone)),
        );
    }

    public static function fromInterface(\DateTimeInterface $dateTime): self
    {
        return new self(\DateTimeImmutable::createFromInterface($dateTime));
    }

    public function toTimezone(string|\DateTimeZone $timezone): self
    {
        return new self($this->dateTime->setTimezone(self::resolveTimezone($timezone)));
    }

    public function timezone(): \DateTimeZone
    {
        return $this->dateTime->getTimezone();
    }

    public function timezoneName(): string
    {
        return $this->timezone()->getName();
    }

    public function format(string $format): string
    {
        return $this->dateTime->format($format);
    }

    public function toIso8601String(): string
    {
        return $this->format(\DateTimeInterface::ATOM);
    }

    public function timestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    public function toNative(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function add(\DateInterval $interval): self
    {
        return new self($this->dateTime->add($interval));
    }

    public function subtract(\DateInterval $interval): self
    {
        return new self($this->dateTime->sub($interval));
    }

    public function addYears(int $years): self
    {
        return $this->modifyBy($years, 'year');
    }

    public function subtractYears(int $years): self
    {
        return $this->modifyBy(-$years, 'year');
    }

    public function addMonths(int $months): self
    {
        return $this->modifyBy($months, 'month');
    }

    public function subtractMonths(int $months): self
    {
        return $this->modifyBy(-$months, 'month');
    }

    public function addDays(int $days): self
    {
        return $this->modifyBy($days, 'day');
    }

    public function subtractDays(int $days): self
    {
        return $this->modifyBy(-$days, 'day');
    }

    public function addWeeks(int $weeks): self
    {
        return $this->addDays($weeks * 7);
    }

    public function subtractWeeks(int $weeks): self
    {
        return $this->subtractDays($weeks * 7);
    }

    public function addQuarters(int $quarters): self
    {
        return $this->addMonths($quarters * 3);
    }

    public function subtractQuarters(int $quarters): self
    {
        return $this->subtractMonths($quarters * 3);
    }

    public function addHours(int $hours): self
    {
        return $this->shiftBySeconds($hours * 3600);
    }

    public function subtractHours(int $hours): self
    {
        return $this->shiftBySeconds(-$hours * 3600);
    }

    public function addMinutes(int $minutes): self
    {
        return $this->shiftBySeconds($minutes * 60);
    }

    public function subtractMinutes(int $minutes): self
    {
        return $this->shiftBySeconds(-$minutes * 60);
    }

    public function addSeconds(int $seconds): self
    {
        return $this->shiftBySeconds($seconds);
    }

    public function subtractSeconds(int $seconds): self
    {
        return $this->shiftBySeconds(-$seconds);
    }

    public function setDate(int $year, int $month, int $day): self
    {
        if (!checkdate($month, $day, $year)) {
            throw new InvalidDateTimeException(
                sprintf('Invalid calendar date [%04d-%02d-%02d].', $year, $month, $day),
            );
        }

        return new self($this->dateTime->setDate($year, $month, $day));
    }

    public function setTime(int $hour, int $minute, int $second = 0, int $microsecond = 0): self
    {
        if (
            $hour < 0
            || $hour > 23
            || $minute < 0
            || $minute > 59
            || $second < 0
            || $second > 59
            || $microsecond < 0
            || $microsecond > 999999
        ) {
            throw new InvalidDateTimeException(
                sprintf('Invalid time [%02d:%02d:%02d.%06d].', $hour, $minute, $second, $microsecond),
            );
        }

        return new self($this->dateTime->setTime($hour, $minute, $second, $microsecond));
    }

    public function startOfDay(): self
    {
        return new self($this->dateTime->setTime(0, 0));
    }

    public function endOfDay(): self
    {
        return new self($this->dateTime->setTime(23, 59, 59, 999999));
    }

    public function startOfWeek(int $firstDay = 1): self
    {
        self::guardWeekday($firstDay);

        $weekday = (int) $this->format('N');
        $daysSinceStart = ($weekday - $firstDay + 7) % 7;

        return $this->subtractDays($daysSinceStart)->startOfDay();
    }

    public function endOfWeek(int $firstDay = 1): self
    {
        return $this->startOfWeek($firstDay)->addDays(6)->endOfDay();
    }

    public function startOfMonth(): self
    {
        return new self($this->dateTime->modify('first day of this month')->setTime(0, 0));
    }

    public function endOfMonth(): self
    {
        return new self($this->dateTime->modify('last day of this month')->setTime(23, 59, 59, 999999));
    }

    public function quarter(): int
    {
        return intdiv((int) $this->format('n') - 1, 3) + 1;
    }

    public function startOfQuarter(): self
    {
        $month = (($this->quarter() - 1) * 3) + 1;

        return $this->setDate((int) $this->format('Y'), $month, 1)->startOfDay();
    }

    public function endOfQuarter(): self
    {
        return $this->startOfQuarter()->addMonths(3)->subtractDays(1)->endOfDay();
    }

    public function startOfYear(): self
    {
        return new self($this->dateTime->setDate((int) $this->format('Y'), 1, 1)->setTime(0, 0));
    }

    public function endOfYear(): self
    {
        return new self($this->dateTime->setDate((int) $this->format('Y'), 12, 31)->setTime(23, 59, 59, 999999));
    }

    public function isBefore(self|\DateTimeInterface $other): bool
    {
        return $this->dateTime < self::native($other);
    }

    public function isAfter(self|\DateTimeInterface $other): bool
    {
        return $this->dateTime > self::native($other);
    }

    public function isSameInstant(self|\DateTimeInterface $other): bool
    {
        return $this->format('U.u') === self::native($other)->format('U.u');
    }

    public function isBetween(
        self|\DateTimeInterface $start,
        self|\DateTimeInterface $end,
        bool $inclusive = true,
    ): bool {
        $start = self::native($start);
        $end = self::native($end);

        if ($end < $start) {
            throw new InvalidDateTimeException('The end of a date-time range must not precede its start.');
        }

        if ($inclusive) {
            return $this->dateTime >= $start && $this->dateTime <= $end;
        }

        return $this->dateTime > $start && $this->dateTime < $end;
    }

    public function min(self|\DateTimeInterface $other): self
    {
        $native = self::native($other);

        if ($this->dateTime <= $native) {
            return $this;
        }

        return $other instanceof self ? $other : new self($native);
    }

    public function max(self|\DateTimeInterface $other): self
    {
        $native = self::native($other);

        if ($this->dateTime >= $native) {
            return $this;
        }

        return $other instanceof self ? $other : new self($native);
    }

    public function diff(self|\DateTimeInterface $other, bool $absolute = false): \DateInterval
    {
        return $this->dateTime->diff(self::native($other), $absolute);
    }

    private function modifyBy(int $amount, string $unit): self
    {
        return new self($this->dateTime->modify(sprintf('%+d %s', $amount, $unit)));
    }

    private function shiftBySeconds(int $seconds): self
    {
        return new self($this->dateTime->setTimestamp($this->timestamp() + $seconds));
    }

    private static function native(self|\DateTimeInterface $dateTime): \DateTimeImmutable
    {
        return $dateTime instanceof self
            ? $dateTime->dateTime
            : \DateTimeImmutable::createFromInterface($dateTime);
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

    private static function guardLastErrors(string $value): void
    {
        $errors = \DateTimeImmutable::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            throw new InvalidDateTimeException('Invalid date-time [' . $value . '].');
        }
    }

    private static function guardWeekday(int $weekday): void
    {
        if ($weekday < 1 || $weekday > 7) {
            throw new InvalidDateTimeException('ISO weekday must be between 1 and 7.');
        }
    }
}
