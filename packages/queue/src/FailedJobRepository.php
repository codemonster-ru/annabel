<?php

namespace Codemonster\Queue;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Queue\Contracts\FailedJobRepositoryInterface;

class FailedJobRepository implements FailedJobRepositoryInterface
{
    public function __construct(
        protected ConnectionInterface $connection,
        protected string $table = 'jobs',
        protected string $failedTable = 'failed_jobs',
        protected int $maxAttempts = 3,
    ) {
        $this->table = $this->normalizeTable($table);
        $this->failedTable = $this->normalizeTable($failedTable);
        $this->maxAttempts = max(1, $maxAttempts);
    }

    /**
     * @return list<FailedJob>
     */
    public function all(): array
    {
        return array_map(
            fn (array $row): FailedJob => $this->restore($row),
            $this->connection->table($this->failedTable)->orderBy('id')->get(),
        );
    }

    public function find(string $id): ?FailedJob
    {
        $row = $this->connection->table($this->failedTable)->where('id', $id)->first();

        return $row ? $this->restore($row) : null;
    }

    public function retry(string $id): bool
    {
        return $this->connection->transaction(function () use ($id): bool {
            $job = $this->find($id);

            if (!$job) {
                return false;
            }

            $this->connection->table($this->table)->insert([
                'queue' => $job->queue(),
                'payload' => $job->payload(),
                'attempts' => 0,
                'max_attempts' => $this->maxAttempts,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time(),
            ]);

            if (!$this->forget($id)) {
                throw new QueueException("Failed job [{$id}] could not be removed after retry.");
            }

            return true;
        });
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
        return $this->connection->table($this->failedTable)->where('id', $id)->delete() === 1;
    }

    public function flush(): int
    {
        return $this->connection->table($this->failedTable)->delete();
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function restore(array $row): FailedJob
    {
        $id = $row['id'] ?? null;
        $connection = $row['connection'] ?? null;
        $queue = $row['queue'] ?? null;
        $payload = $row['payload'] ?? null;
        $exception = $row['exception'] ?? null;
        $failedAt = $row['failed_at'] ?? null;

        if ((!is_string($id) && !is_int($id))
            || !is_string($connection)
            || !is_string($queue)
            || !is_string($payload)
            || ($exception !== null && !is_string($exception))
            || (!is_string($failedAt) && !is_int($failedAt))) {
            throw new QueueException('Failed job row contains invalid data.');
        }

        return new FailedJob(
            (string) $id,
            $connection,
            $queue,
            $payload,
            $exception,
            (int) $failedAt,
        );
    }

    protected function normalizeTable(string $table): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new QueueException("Invalid queue table name [{$table}].");
        }

        return $table;
    }
}
