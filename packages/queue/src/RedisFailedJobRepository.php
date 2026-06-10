<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\FailedJobRepositoryInterface;

class RedisFailedJobRepository implements FailedJobRepositoryInterface
{
    public function __construct(
        protected object $client,
        protected RedisQueue $queue,
    ) {
    }

    public function all(): array
    {
        $ids = $this->zrange($this->queue->failedIndexKey(), 0, -1);
        $jobs = [];

        foreach ($ids as $id) {
            $job = $this->find($id);
            if ($job) {
                $jobs[] = $job;
            }
        }

        return $jobs;
    }

    public function find(string $id): ?FailedJob
    {
        $payload = $this->invoke('hGet', $this->queue->failedKey(), $id);

        return is_string($payload) ? $this->restore($payload) : null;
    }

    public function retry(string $id): bool
    {
        $job = $this->find($id);

        if (!$job) {
            return false;
        }

        $this->queue->pushPayload($job->payload(), $job->queue());

        if (!$this->forget($id)) {
            throw new QueueException("Failed job [{$id}] could not be removed after retry.");
        }

        return true;
    }

    public function retryAll(): int
    {
        $retried = 0;

        foreach ($this->all() as $job) {
            if ($this->retry($job->id())) {
                $retried++;
            }
        }

        return $retried;
    }

    public function forget(string $id): bool
    {
        $removed = $this->invoke('hDel', $this->queue->failedKey(), $id);
        $this->invoke('zRem', $this->queue->failedIndexKey(), $id);

        return $removed === true || $removed === 1 || $removed === '1';
    }

    public function flush(): int
    {
        $count = count($this->all());
        $this->invoke('del', $this->queue->failedKey());
        $this->invoke('del', $this->queue->failedIndexKey());

        return $count;
    }

    protected function restore(string $payload): FailedJob
    {
        $data = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($data)
            || !is_string($data['id'] ?? null)
            || !is_string($data['connection'] ?? null)
            || !is_string($data['queue'] ?? null)
            || !is_string($data['payload'] ?? null)
            || (($data['exception'] ?? null) !== null && !is_string($data['exception']))
            || !is_int($data['failed_at'] ?? null)) {
            throw new QueueException('Failed Redis job contains invalid data.');
        }

        $exception = $data['exception'] ?? null;

        return new FailedJob(
            $data['id'],
            $data['connection'],
            $data['queue'],
            base64_decode($data['payload'], true) ?: '',
            is_string($exception) ? $exception : null,
            $data['failed_at'],
        );
    }

    /** @return list<string> */
    protected function zrange(string $key, int $start, int $stop): array
    {
        $items = $this->invoke('zRange', $key, $start, $stop);

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, 'is_string'));
    }

    protected function invoke(string $method, mixed ...$arguments): mixed
    {
        $callable = [$this->client, $method];

        if (!is_callable($callable)) {
            throw new QueueException("Redis client does not support [{$method}].");
        }

        return $callable(...$arguments);
    }
}
