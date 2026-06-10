<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Queue\QueueManager;

class QueueFlushCommand extends Command
{
    public function getName(): string
    {
        return 'queue:flush';
    }

    public function getDescription(): string
    {
        return 'Delete all failed queued jobs.';
    }

    public function getUsage(): string
    {
        return 'queue:flush [--connection=database]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $input->option('connection');
        $connection = is_string($connection) && $connection !== '' ? $connection : null;
        $count = $this->console()
            ->getApplication()
            ->make(QueueManager::class)
            ->failedJobs($connection)
            ->flush();

        $output->writeln("Deleted {$count} failed job(s).");

        return ExitCode::SUCCESS;
    }
}
