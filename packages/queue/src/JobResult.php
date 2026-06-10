<?php

namespace Codemonster\Queue;

class JobResult
{
    public function __construct(
        protected string $id,
        protected string $connection,
        protected string $queue,
        protected bool $processed,
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

    public function processed(): bool
    {
        return $this->processed;
    }
}
