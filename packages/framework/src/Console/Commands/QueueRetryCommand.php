<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Queue\QueueManager;

class QueueRetryCommand extends Command
{
    public function getName(): string
    {
        return 'queue:retry';
    }

    public function getDescription(): string
    {
        return 'Retry a failed queued job.';
    }

    public function getUsage(): string
    {
        return 'queue:retry <id|all> [--connection=database]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->arguments()[0] ?? null;

        if (!is_string($id) || $id === '') {
            $output->writeln('A failed job ID or "all" is required.');

            return ExitCode::INVALID;
        }

        $repository = $this->manager()->failedJobs($this->connection($input));

        if ($id === 'all') {
            $count = $repository->retryAll();
            $output->writeln("Retried {$count} failed job(s).");

            return ExitCode::SUCCESS;
        }

        if (!$repository->retry($id)) {
            $output->writeln("Failed job [{$id}] was not found.");

            return ExitCode::FAILURE;
        }

        $output->writeln("Retried failed job [{$id}].");

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
