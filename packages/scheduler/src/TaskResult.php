<?php

namespace Codemonster\Scheduler;

class TaskResult
{
    private function __construct(
        protected string $status,
        protected string $description,
        protected ?\Throwable $exception = null,
    ) {
    }

    public static function succeeded(string $description): self
    {
        return new self('succeeded', $description);
    }

    public static function failed(string $description, \Throwable $exception): self
    {
        return new self('failed', $description, $exception);
    }

    public static function skipped(string $description): self
    {
        return new self('skipped', $description);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function exception(): ?\Throwable
    {
        return $this->exception;
    }
}
