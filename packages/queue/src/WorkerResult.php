<?php

namespace Codemonster\Queue;

class WorkerResult
{
    private function __construct(
        protected string $status,
        protected ?string $jobId = null,
        protected ?\Throwable $exception = null,
    ) {
    }

    public static function idle(): self
    {
        return new self('idle');
    }

    public static function processed(string $jobId): self
    {
        return new self('processed', $jobId);
    }

    public static function failed(string $jobId, \Throwable $exception): self
    {
        return new self('failed', $jobId, $exception);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function jobId(): ?string
    {
        return $this->jobId;
    }

    public function exception(): ?\Throwable
    {
        return $this->exception;
    }
}
