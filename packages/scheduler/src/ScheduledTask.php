<?php

namespace Codemonster\Scheduler;

use Codemonster\Scheduler\Contracts\LockStoreInterface;

class ScheduledTask
{
    /** @var list<int> */
    protected array $minutes;
    /** @var list<int> */
    protected array $hours;
    /** @var list<int> */
    protected array $daysOfMonth;
    /** @var list<int> */
    protected array $months;
    /** @var list<int> */
    protected array $weekdays;
    /** @var list<callable(\DateTimeInterface):bool> */
    protected array $filters = [];
    protected bool $withoutOverlapping = false;
    protected int $overlapExpiresAfter = 86400;
    protected ?string $overlapName = null;

    /** @param \Closure():mixed $callback */
    public function __construct(
        protected \Closure $callback,
        protected string $description = 'Closure',
    ) {
        $this->minutes = range(0, 59);
        $this->hours = range(0, 23);
        $this->daysOfMonth = range(1, 31);
        $this->months = range(1, 12);
        $this->weekdays = range(0, 6);
    }

    public function description(): string
    {
        return $this->description;
    }

    public function name(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function everyMinute(): self
    {
        return $this->minutes(range(0, 59));
    }

    public function everyFiveMinutes(): self
    {
        return $this->everyMinutes(5);
    }

    public function everyTenMinutes(): self
    {
        return $this->everyMinutes(10);
    }

    public function everyFifteenMinutes(): self
    {
        return $this->everyMinutes(15);
    }

    public function everyThirtyMinutes(): self
    {
        return $this->everyMinutes(30);
    }

    public function hourly(): self
    {
        return $this->minutes([0]);
    }

    public function dailyAt(string $time): self
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $matches)) {
            throw new ScheduleException("Invalid daily time [{$time}].");
        }

        return $this
            ->hours([(int) $matches[1]])
            ->minutes([(int) $matches[2]]);
    }

    public function weekdays(): self
    {
        return $this->setWeekdays([1, 2, 3, 4, 5]);
    }

    public function weekends(): self
    {
        return $this->setWeekdays([0, 6]);
    }

    /**
     * @param callable(\DateTimeInterface):bool $filter
     */
    public function when(callable $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    public function withoutOverlapping(int $expiresAfterMinutes = 1440, ?string $name = null): self
    {
        if ($expiresAfterMinutes < 1) {
            throw new ScheduleException('Overlap lock expiration must be at least 1 minute.');
        }

        $this->withoutOverlapping = true;
        $this->overlapExpiresAfter = $expiresAfterMinutes * 60;
        $this->overlapName = $name;

        return $this;
    }

    public function isDue(?\DateTimeInterface $now = null): bool
    {
        $now ??= new \DateTimeImmutable();

        if (!in_array((int) $now->format('i'), $this->minutes, true)
            || !in_array((int) $now->format('G'), $this->hours, true)
            || !in_array((int) $now->format('j'), $this->daysOfMonth, true)
            || !in_array((int) $now->format('n'), $this->months, true)
            || !in_array((int) $now->format('w'), $this->weekdays, true)) {
            return false;
        }

        foreach ($this->filters as $filter) {
            if (!$filter($now)) {
                return false;
            }
        }

        return true;
    }

    public function run(?LockStoreInterface $lockStore = null): TaskResult
    {
        $lockName = null;
        if ($this->withoutOverlapping) {
            $lockStore ??= new ArrayLockStore();
            $lockName = $this->overlapLockName();

            if (!$lockStore->acquire($lockName, $this->overlapExpiresAfter)) {
                return TaskResult::skipped($this->description);
            }
        }

        try {
            ($this->callback)();

            return TaskResult::succeeded($this->description);
        } catch (\Throwable $e) {
            return TaskResult::failed($this->description, $e);
        } finally {
            if ($lockStore !== null && $lockName !== null) {
                $lockStore->release($lockName);
            }
        }
    }

    public function overlapLockName(): string
    {
        return 'schedule.lock.' . sha1($this->overlapName ?? $this->description);
    }

    /**
     * @return array{minutes: list<int>, hours: list<int>, days_of_month: list<int>, months: list<int>, weekdays: list<int>}
     */
    public function expression(): array
    {
        return [
            'minutes' => $this->minutes,
            'hours' => $this->hours,
            'days_of_month' => $this->daysOfMonth,
            'months' => $this->months,
            'weekdays' => $this->weekdays,
        ];
    }

    public function preventsOverlaps(): bool
    {
        return $this->withoutOverlapping;
    }

    public function overlapExpiresAfter(): int
    {
        return $this->overlapExpiresAfter;
    }

    protected function everyMinutes(int $step): self
    {
        $minutes = [];
        for ($minute = 0; $minute < 60; $minute += $step) {
            $minutes[] = $minute;
        }

        return $this->minutes($minutes);
    }

    /**
     * @param list<int> $minutes
     */
    protected function minutes(array $minutes): self
    {
        $this->minutes = $this->normalize($minutes, 0, 59, 'minute');

        return $this;
    }

    /**
     * @param list<int> $hours
     */
    protected function hours(array $hours): self
    {
        $this->hours = $this->normalize($hours, 0, 23, 'hour');

        return $this;
    }

    /**
     * @param list<int> $weekdays
     */
    protected function setWeekdays(array $weekdays): self
    {
        $this->weekdays = $this->normalize($weekdays, 0, 6, 'weekday');

        return $this;
    }

    /**
     * @param list<int> $values
     * @return list<int>
     */
    protected function normalize(array $values, int $min, int $max, string $label): array
    {
        $normalized = array_values(array_unique($values));
        sort($normalized);

        foreach ($normalized as $value) {
            if ($value < $min || $value > $max) {
                throw new ScheduleException("Invalid {$label} value [{$value}].");
            }
        }

        return $normalized;
    }
}
