<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Queue\Worker;

class QueueWorkCommand extends Command
{
    public function getName(): string
    {
        return 'queue:work';
    }

    public function getDescription(): string
    {
        return 'Process queued jobs.';
    }

    public function getUsage(): string
    {
        return 'queue:work [queue] [--once] [--stop-when-empty] [--sleep=3] [--max-jobs=0]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $queue = $input->arguments()[0] ?? null;
        $sleep = $this->intOption($input, 'sleep', 3);
        $maxJobs = $this->intOption($input, 'max-jobs', $input->hasOption('once') ? 1 : 0);
        $processed = 0;
        $stopRequested = false;
        $restoreSignals = $this->listenForStopSignals($stopRequested);

        /** @var Worker $worker */
        $worker = $this->console()->getApplication()->make(Worker::class);

        try {
            do {
                $result = $worker->workOnce($queue);

                if ($result->status() === 'processed') {
                    $processed++;
                    $output->writeln('Processed job: ' . $result->jobId());
                } elseif ($result->status() === 'failed') {
                    $processed++;
                    $message = $result->exception()?->getMessage() ?? 'Unknown error';
                    $output->writeln('Failed job: ' . $result->jobId() . ' (' . $message . ')');
                } elseif ($maxJobs === 1) {
                    $output->writeln('No jobs available.');
                }

                if ($stopRequested
                    || ($maxJobs > 0 && $processed >= $maxJobs)
                    || ($result->status() === 'idle' && $input->hasOption('stop-when-empty'))) {
                    break;
                }

                if ($result->status() === 'idle') {
                    sleep(max(0, $sleep));
                }
            } while (!$input->hasOption('once'));
        } finally {
            $restoreSignals();
        }

        return ExitCode::SUCCESS;
    }

    private function intOption(InputInterface $input, string $name, int $default): int
    {
        $value = $input->option($name);

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * @param bool $stopRequested
     * @return \Closure():void
     */
    private function listenForStopSignals(bool &$stopRequested): \Closure
    {
        if (!function_exists('pcntl_async_signals')
            || !function_exists('pcntl_signal')
            || !function_exists('pcntl_signal_get_handler')) {
            return static function (): void {
            };
        }

        $asyncSignals = pcntl_async_signals(true);
        $handlers = [
            SIGTERM => pcntl_signal_get_handler(SIGTERM),
            SIGINT => pcntl_signal_get_handler(SIGINT),
        ];

        foreach (array_keys($handlers) as $signal) {
            pcntl_signal($signal, static function () use (&$stopRequested): void {
                $stopRequested = true;
            });
        }

        return static function () use ($asyncSignals, $handlers): void {
            foreach ($handlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
            pcntl_async_signals($asyncSignals);
        };
    }
}
