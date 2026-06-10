<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Queue\Contracts\QueueInterface;
use Codemonster\Queue\Contracts\WorkableQueueInterface;
use Codemonster\Queue\QueueManager;
use Codemonster\Queue\Worker;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/queue.php' => $this->app()->getBasePath() . '/config/queue.php',
        ], ['config', 'queue']);
        $this->publishes([
            __DIR__ . '/../../database/migrations/2026_06_09_000001_create_jobs_table.php' => $this->app()->getBasePath() . '/database/migrations/2026_06_09_000001_create_jobs_table.php',
            __DIR__ . '/../../database/migrations/2026_06_09_000002_create_failed_jobs_table.php' => $this->app()->getBasePath() . '/database/migrations/2026_06_09_000002_create_failed_jobs_table.php',
        ], ['migrations', 'queue-migrations']);

        $this->app()->singleton(QueueManager::class, fn (Container $app): QueueManager => new QueueManager(
            $this->queueConfig(),
            fn (): ConnectionInterface => $app->make(ConnectionInterface::class),
        ));
        $this->app()->singleton('queue.manager', fn (Container $app): QueueManager => $app->make(QueueManager::class));
        $this->app()->singleton(QueueInterface::class, fn (Container $app): QueueInterface => $app
            ->make(QueueManager::class)
            ->connection());
        $this->app()->singleton('queue', fn (Container $app): QueueInterface => $app->make(QueueInterface::class));
        $this->app()->singleton(Worker::class, function (Container $app): Worker {
            $queue = $app->make(QueueManager::class)->connection();

            if (!$queue instanceof WorkableQueueInterface) {
                throw new \RuntimeException('Queue worker requires a workable queue connection, such as the database driver.');
            }

            return new Worker(
                $queue,
                $this->backoffConfig(),
                $this->intConfig('queue.timeout', 0),
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function queueConfig(): array
    {
        $config = config('queue', [
            'default' => 'sync',
            'connections' => [
                'sync' => [
                    'driver' => 'sync',
                ],
            ],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('Queue config must be an array.');
        }

        $normalized = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    private function intConfig(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_int($value) ? $value : $default;
    }

    /** @return int|list<int> */
    private function backoffConfig(): int|array
    {
        $value = config('queue.backoff', 0);

        if (is_int($value)) {
            return $value;
        }

        if (!is_array($value)) {
            return 0;
        }

        $backoff = [];
        foreach ($value as $delay) {
            if (is_int($delay)) {
                $backoff[] = $delay;
            }
        }

        return $backoff;
    }
}
