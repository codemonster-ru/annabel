<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\DateTime\DateTime;
use Codemonster\Queue\QueueManager;
use Psr\Clock\ClockInterface;

class QueueFailedCommand extends Command
{
    public function getName(): string
    {
        return 'queue:failed';
    }

    public function getDescription(): string
    {
        return 'List failed queued jobs.';
    }

    public function getUsage(): string
    {
        return 'queue:failed [--connection=database]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobs = $this->manager()->failedJobs($this->connection($input))->all();
        $timezone = $this->console()->getApplication()->make(ClockInterface::class)->now()->getTimezone();

        if ($jobs === []) {
            $output->writeln('No failed jobs.');

            return ExitCode::SUCCESS;
        }

        foreach ($jobs as $job) {
            $output->writeln(sprintf(
                '%s  %s  %s  %s  %s',
                $job->id(),
                $job->connection(),
                $job->queue(),
                DateTime::fromTimestamp($job->failedAt(), $timezone)->format(DateTime::DATABASE_FORMAT),
                $job->exception() ?? '-',
            ));
        }

        return ExitCode::SUCCESS;
    }

    private function manager(): QueueManager
    {
        return $this->console()->getApplication()->make(QueueManager::class);
    }

    private function connection(InputInterface $input): ?string
    {
        $connection = $input->option('connection');

        return is_string($connection) && $connection !== '' ? $connection : null;
    }
}
