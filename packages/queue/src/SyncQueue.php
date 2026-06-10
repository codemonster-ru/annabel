<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\QueueInterface;

class SyncQueue implements QueueInterface
{
    public function __construct(protected string $connection = 'sync')
    {
    }

    public function push(JobInterface $job, ?string $queue = null): JobResult
    {
        $queue = $this->normalizeQueue($queue);
        $job->handle();

        return new JobResult($this->id(), $this->connection, $queue, true);
    }

    protected function normalizeQueue(?string $queue): string
    {
        return is_string($queue) && $queue !== '' ? $queue : 'default';
    }

    protected function id(): string
    {
        return bin2hex(random_bytes(16));
    }
}
