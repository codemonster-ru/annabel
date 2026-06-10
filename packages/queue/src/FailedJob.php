<?php

namespace Codemonster\Queue;

final class FailedJob
{
    public function __construct(
        protected string $id,
        protected string $connection,
        protected string $queue,
        protected string $payload,
        protected ?string $exception,
        protected int $failedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function connection(): string
    {
        return $this->connection;
    }

    public function queue(): string
    {
        return $this->queue;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function exception(): ?string
    {
        return $this->exception;
    }

    public function failedAt(): int
    {
        return $this->failedAt;
    }
}
