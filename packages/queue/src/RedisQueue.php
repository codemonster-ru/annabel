<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\JobOptionsInterface;
use Codemonster\Queue\Contracts\WorkableQueueInterface;

class RedisQueue implements WorkableQueueInterface
{
    public function __construct(
        protected object $client,
        protected string $connectionName = 'redis',
        protected string $prefix = 'queue:',
        protected int $retryAfter = 60,
        protected int $maxAttempts = 3,
        protected ?JobSerializer $serializer = null,
    ) {
        $this->retryAfter = max(1, $retryAfter);
        $this->maxAttempts = max(1, $maxAttempts);
        $this->serializer ??= new JobSerializer();
    }

    public function push(JobInterface $job, ?string $queue = null): JobResult
    {
        $maxAttempts = $job instanceof JobOptionsInterface
            ? max(1, $job->maxAttempts())
            : $this->maxAttempts;

        return $this->pushPayload(
            $this->serializer()->serialize($job),
            $queue,
            $maxAttempts,
        );
    }

    public function pushPayload(string $payload, ?string $queue = null, ?int $maxAttempts = null): JobResult
    {
        $queue = $this->normalizeQueue($queue);
        $id = $this->id();
        $envelope = $this->encode([
            'id' => $id,
            'connection' => $this->connectionName,
            'queue' => $queue,
            'payload' => base64_encode($payload),
            'attempts' => 0,
            'max_attempts' => max(1, $maxAttempts ?? $this->maxAttempts),
            'reserved_at' => null,
            'available_at' => $this->now(),
            'created_at' => $this->now(),
        ]);

        $this->invoke('rPush', $this->readyKey($queue), $envelope);

        return new JobResult($id, $this->connectionName, $queue, false);
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        $queue = $this->normalizeQueue($queue);
        $this->migrateExpired($this->delayedKey($queue), $this->readyKey($queue), $this->now());
        $this->migrateExpired($this->reservedKey($queue), $this->readyKey($queue), $this->now());

        $envelope = $this->invoke('lPop', $this->readyKey($queue));

        if (!is_string($envelope)) {
            return null;
        }

        $data = $this->decode($envelope);
        $data['attempts']++;
        $data['reserved_at'] = $this->now();
        $reservedEnvelope = $this->encode($data);
        $this->zadd($this->reservedKey($queue), $this->now() + $this->retryAfter, $reservedEnvelope);

        return $this->restore($reservedEnvelope, $data);
    }

    /**
     * @param array{
     *   id:string,
     *   connection:string,
     *   queue:string,
     *   payload:string,
     *   attempts:int,
     *   max_attempts:int,
     *   reserved_at:int|null,
     *   available_at:int,
     *   created_at:int
     * } $data
     */
    protected function restore(string $envelope, array $data): QueuedJob
    {
        return new QueuedJob(
            $data['id'],
            $data['connection'],
            $data['queue'],
            $this->serializer()->deserialize(base64_decode($data['payload'], true) ?: ''),
            $data['attempts'],
            $data['max_attempts'],
            function (QueuedJob $job) use ($envelope): void {
                $this->delete($job, $envelope);
            },
            function (QueuedJob $job, int $delay) use ($envelope): void {
                $this->release($job, $envelope, $delay);
            },
            function (QueuedJob $job, ?\Throwable $exception) use ($envelope, $data): void {
                $this->fail($job, $envelope, base64_decode($data['payload'], true) ?: '', $exception);
            },
        );
    }

    protected function delete(QueuedJob $job, string $envelope): void
    {
        $this->invoke('zRem', $this->reservedKey($job->queue()), $envelope);
    }

    protected function release(QueuedJob $job, string $envelope, int $delay): void
    {
        $this->invoke('zRem', $this->reservedKey($job->queue()), $envelope);
        $this->zadd($this->delayedKey($job->queue()), $this->now() + max(0, $delay), $envelope);
    }

    protected function fail(QueuedJob $job, string $envelope, string $payload, ?\Throwable $exception): void
    {
        $this->invoke('zRem', $this->reservedKey($job->queue()), $envelope);
        $failedAt = $this->now();
        $failed = $this->encodeFailed([
            'id' => $job->id(),
            'connection' => $job->connection(),
            'queue' => $job->queue(),
            'payload' => base64_encode($payload),
            'exception' => $exception ? $exception::class . ': ' . $exception->getMessage() : null,
            'failed_at' => $failedAt,
        ]);
        $this->invoke('hSet', $this->failedKey(), $job->id(), $failed);
        $this->zadd($this->failedIndexKey(), $failedAt, $job->id());
    }

    protected function migrateExpired(string $from, string $to, int $now): void
    {
        foreach ($this->zrangeByScore($from, '-inf', (string) $now) as $envelope) {
            $this->invoke('zRem', $from, $envelope);
            $this->invoke('rPush', $to, $envelope);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function encode(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{
     *   id:string,
     *   connection:string,
     *   queue:string,
     *   payload:string,
     *   attempts:int,
     *   max_attempts:int,
     *   reserved_at:int|null,
     *   available_at:int,
     *   created_at:int
     * }
     */
    protected function decode(string $envelope): array
    {
        $data = json_decode($envelope, true, flags: JSON_THROW_ON_ERROR);

        if (!is_array($data)
            || !is_string($data['id'] ?? null)
            || !is_string($data['connection'] ?? null)
            || !is_string($data['queue'] ?? null)
            || !is_string($data['payload'] ?? null)
            || !is_int($data['attempts'] ?? null)
            || !is_int($data['max_attempts'] ?? null)
            || (($data['reserved_at'] ?? null) !== null && !is_int($data['reserved_at']))
            || !is_int($data['available_at'] ?? null)
            || !is_int($data['created_at'] ?? null)) {
            throw new QueueException('Redis queued job contains invalid metadata.');
        }

        return [
            'id' => $data['id'],
            'connection' => $data['connection'],
            'queue' => $data['queue'],
            'payload' => $data['payload'],
            'attempts' => $data['attempts'],
            'max_attempts' => max(1, $data['max_attempts']),
            'reserved_at' => is_int($data['reserved_at'] ?? null) ? $data['reserved_at'] : null,
            'available_at' => $data['available_at'],
            'created_at' => $data['created_at'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function encodeFailed(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function normalizeQueue(?string $queue): string
    {
        return is_string($queue) && $queue !== '' ? $queue : 'default';
    }

    protected function readyKey(string $queue): string
    {
        return $this->prefix . 'queues:' . $queue . ':ready';
    }

    protected function delayedKey(string $queue): string
    {
        return $this->prefix . 'queues:' . $queue . ':delayed';
    }

    protected function reservedKey(string $queue): string
    {
        return $this->prefix . 'queues:' . $queue . ':reserved';
    }

    public function failedKey(): string
    {
        return $this->prefix . 'failed';
    }

    public function failedIndexKey(): string
    {
        return $this->prefix . 'failed:index';
    }

    protected function zadd(string $key, int $score, string $member): void
    {
        $this->invoke('zAdd', $key, $score, $member);
    }

    /** @return list<string> */
    protected function zrangeByScore(string $key, string $min, string $max): array
    {
        $items = $this->invoke('zRangeByScore', $key, $min, $max);

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

    protected function id(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function now(): int
    {
        return time();
    }

    protected function serializer(): JobSerializer
    {
        if (!$this->serializer) {
            $this->serializer = new JobSerializer();
        }

        return $this->serializer;
    }
}
