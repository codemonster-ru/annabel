<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\JobInterface;

class QueuedJob
{
    /**
     * @param \Closure(self):void $delete
     * @param \Closure(self, int):void $release
     * @param \Closure(self, \Throwable|null):void $fail
     */
    public function __construct(
        protected string $id,
        protected string $connection,
        protected string $queue,
        protected JobInterface $job,
        protected int $attempts,
        protected int $maxAttempts,
        protected \Closure $delete,
        protected \Closure $release,
        protected \Closure $fail,
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

    public function job(): JobInterface
    {
        return $this->job;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function handle(): void
    {
        $this->job->handle();
    }

    public function delete(): void
    {
        ($this->delete)($this);
    }

    public function release(int $delay = 0): void
    {
        ($this->release)($this, $delay);
    }

    public function fail(?\Throwable $exception = null): void
    {
        ($this->fail)($this, $exception);
    }
}
