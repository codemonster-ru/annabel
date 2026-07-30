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

    public function startOfDay(): self
    {
        return new self($this->dateTime->setTime(0, 0));
    }

    public function endOfDay(): self
    {
        return new self($this->dateTime->setTime(23, 59, 59, 999999));
    }

    public function startOfMonth(): self
    {
        return new self($this->dateTime->modify('first day of this month')->setTime(0, 0));
    }

    public function endOfMonth(): self
    {
        return new self($this->dateTime->modify('last day of this month')->setTime(23, 59, 59, 999999));
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
}
