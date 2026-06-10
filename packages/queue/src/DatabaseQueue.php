<?php

namespace Codemonster\Queue;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\JobOptionsInterface;
use Codemonster\Queue\Contracts\WorkableQueueInterface;

class DatabaseQueue implements WorkableQueueInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $connectionName = 'database',
        protected string $table = 'jobs',
        protected string $failedTable = 'failed_jobs',
        protected int $retryAfter = 60,
        protected int $maxAttempts = 3,
        protected ?JobSerializer $serializer = null,
    ) {
        $this->table = $this->normalizeTable($table);
        $this->failedTable = $this->normalizeTable($failedTable);
        $this->retryAfter = max(1, $retryAfter);
        $this->maxAttempts = max(1, $maxAttempts);
        $this->serializer ??= new JobSerializer();
    }

    public function push(JobInterface $job, ?string $queue = null): JobResult
    {
        $queue = $this->normalizeQueue($queue);
        $maxAttempts = $job instanceof JobOptionsInterface
            ? max(1, $job->maxAttempts())
            : $this->maxAttempts;
        $id = (string) $this->connection->table($this->table)->insertGetId([
            'queue' => $queue,
            'payload' => $this->serializer()->serialize($job),
            'attempts' => 0,
            'max_attempts' => $maxAttempts,
            'reserved_at' => null,
            'available_at' => $this->now(),
            'created_at' => $this->now(),
        ]);

        return new JobResult($id, $this->connectionName, $queue, false);
    }

    public function pop(?string $queue = null): ?QueuedJob
    {
        $queue = $this->normalizeQueue($queue);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $row = $this->candidate($queue);

            if (!$row) {
                return null;
            }

            $now = $this->now();
            $affected = $this->connection->update(
                sprintf(
                    'UPDATE %s SET reserved_at = ?, attempts = attempts + 1 WHERE id = ? AND queue = ? AND available_at <= ? AND (reserved_at IS NULL OR reserved_at <= ?)',
                    $this->wrapTable($this->table),
                ),
                [$now, $row['id'], $queue, $now, $this->expiredAt($now)],
            );

            if ($affected !== 1) {
                continue;
            }

            $reserved = $this->connection->table($this->table)->where('id', $row['id'])->first();

            return $reserved ? $this->restore($reserved) : null;
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function candidate(string $queue): ?array
    {
        $now = $this->now();

        return $this->connection
            ->table($this->table)
            ->where('queue', $queue)
            ->where('available_at', '<=', $now)
            ->whereRaw('(reserved_at IS NULL OR reserved_at <= ?)', [$this->expiredAt($now)])
            ->orderBy('id')
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function restore(array $row): QueuedJob
    {
        $payload = $row['payload'] ?? null;
        if (!is_string($payload)) {
            throw new QueueException('Queued payload must be a string.');
        }
        $id = $row['id'] ?? null;
        $queue = $row['queue'] ?? null;
        $attempts = $row['attempts'] ?? null;
        $maxAttempts = $row['max_attempts'] ?? null;

        if ((!is_string($id) && !is_int($id))
            || !is_string($queue)
            || (!is_int($attempts) && !is_string($attempts))
            || (!is_int($maxAttempts) && !is_string($maxAttempts))
            || (int) $maxAttempts < 1) {
            throw new QueueException('Queued row contains invalid metadata.');
        }

        return new QueuedJob(
            (string) $id,
            $this->connectionName,
            $queue,
            $this->serializer()->deserialize($payload),
            (int) $attempts,
            (int) $maxAttempts,
            function (QueuedJob $job): void {
                $this->delete($job);
            },
            function (QueuedJob $job, int $delay): void {
                $this->release($job, $delay);
            },
            function (QueuedJob $job, ?\Throwable $exception): void {
                $this->fail($job, $exception);
            },
        );
    }

    protected function delete(QueuedJob $job): void
    {
        $this->connection->table($this->table)->where('id', $job->id())->delete();
    }

    protected function release(QueuedJob $job, int $delay): void
    {
        $this->connection->table($this->table)->where('id', $job->id())->update([
            'reserved_at' => null,
            'available_at' => $this->now() + max(0, $delay),
        ]);
    }

    protected function fail(QueuedJob $job, ?\Throwable $exception): void
    {
        $this->connection->table($this->failedTable)->insert([
            'connection' => $job->connection(),
            'queue' => $job->queue(),
            'payload' => $this->serializer()->serialize($job->job()),
            'exception' => $exception ? $exception::class . ': ' . $exception->getMessage() : null,
            'failed_at' => $this->now(),
        ]);

        $this->delete($job);
    }

    protected function normalizeQueue(?string $queue): string
    {
        return is_string($queue) && $queue !== '' ? $queue : 'default';
    }

    protected function normalizeTable(string $table): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new QueueException("Invalid queue table name [{$table}].");
        }

        return $table;
    }

    protected function wrapTable(string $table): string
    {
        return '`' . $table . '`';
    }

    protected function expiredAt(int $now): int
    {
        return $now - $this->retryAfter;
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
