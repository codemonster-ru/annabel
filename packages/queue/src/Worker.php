<?php

namespace Codemonster\Queue;

use Codemonster\Queue\Contracts\JobOptionsInterface;
use Codemonster\Queue\Contracts\WorkableQueueInterface;

class Worker
{
    /** @var int|list<int> */
    protected int|array $backoff;

    /** @param int|list<int> $backoff */
    public function __construct(
        protected WorkableQueueInterface $queue,
        int|array $backoff = 0,
        protected int $timeout = 0,
    ) {
        $this->backoff = $this->normalizeBackoff($backoff);
        $this->timeout = max(0, $timeout);
    }

    public function workOnce(?string $queue = null): WorkerResult
    {
        $job = $this->queue->pop($queue);

        if (!$job) {
            return WorkerResult::idle();
        }

        try {
            if ($job->attempts() > $job->maxAttempts()) {
                throw new QueueException("Queued job [{$job->id()}] exceeded maximum attempts.");
            }

            $this->handle($job);
            $job->delete();

            return WorkerResult::processed($job->id());
        } catch (\Throwable $e) {
            if ($job->attempts() >= $job->maxAttempts()) {
                $job->fail($e);

                return WorkerResult::failed($job->id(), $e);
            }

            $job->release($this->backoffFor($job));

            return WorkerResult::failed($job->id(), $e);
        }
    }

    protected function handle(QueuedJob $job): void
    {
        $timeout = $job->job() instanceof JobOptionsInterface
            ? max(0, $job->job()->timeout())
            : $this->timeout;

        if ($timeout === 0) {
            $job->handle();

            return;
        }

        if (!function_exists('pcntl_alarm')
            || !function_exists('pcntl_signal')
            || !function_exists('pcntl_signal_get_handler')
            || !function_exists('pcntl_async_signals')) {
            throw new QueueException('Job timeouts require the PCNTL extension.');
        }

        $asyncSignals = pcntl_async_signals(true);
        $previousHandler = pcntl_signal_get_handler(SIGALRM);
        pcntl_signal(SIGALRM, static function () use ($job, $timeout): never {
            throw new QueueTimeoutException("Queued job [{$job->id()}] exceeded its {$timeout} second timeout.");
        });
        pcntl_alarm($timeout);

        try {
            $job->handle();
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, $previousHandler);
            pcntl_async_signals($asyncSignals);
        }
    }

    protected function backoffFor(QueuedJob $job): int
    {
        $backoff = $job->job() instanceof JobOptionsInterface
            ? $job->job()->backoff()
            : $this->backoff;
        $backoff = $this->normalizeBackoff($backoff);

        if (is_int($backoff)) {
            return $backoff;
        }

        $index = max(0, min($job->attempts() - 1, count($backoff) - 1));

        return $backoff[$index];
    }

    /**
     * @param int|array<mixed> $backoff
     * @return int|list<int>
     */
    protected function normalizeBackoff(int|array $backoff): int|array
    {
        if (is_int($backoff)) {
            return max(0, $backoff);
        }

        if ($backoff === []) {
            return 0;
        }

        $normalized = [];
        foreach ($backoff as $delay) {
            if (!is_int($delay)) {
                throw new \InvalidArgumentException('Queue backoff values must be integers.');
            }
            $normalized[] = max(0, $delay);
        }

        return $normalized;
    }
}
