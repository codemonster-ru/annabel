<?php

declare(strict_types=1);

namespace Codemonster\DateTime;

use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    private \DateTimeImmutable $dateTime;

    public function __construct(DateTime|\DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime instanceof DateTime
            ? $dateTime->toNative()
            : \DateTimeImmutable::createFromInterface($dateTime);
    }

    public function now(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function advance(\DateInterval $interval): self
    {
        return new self($this->dateTime->add($interval));
    }

    public function rewind(\DateInterval $interval): self
    {
        return new self($this->dateTime->sub($interval));
    }
}
