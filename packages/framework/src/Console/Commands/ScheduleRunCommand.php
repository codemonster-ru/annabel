<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Scheduler\Schedule;

class ScheduleRunCommand extends Command
{
    public function getName(): string
    {
        return 'schedule:run';
    }

    public function getDescription(): string
    {
        return 'Run due scheduled tasks.';
    }

    public function getUsage(): string
    {
        return 'schedule:run';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Schedule $schedule */
        $schedule = $this->console()->getApplication()->make(Schedule::class);
        $results = $schedule->runDue();

        if ($results === []) {
            $output->writeln('No scheduled tasks are due.');

            return ExitCode::SUCCESS;
        }

        $failed = false;
        foreach ($results as $result) {
            if ($result->status() === 'succeeded') {
                $output->writeln('Ran: ' . $result->description());

                continue;
            }

            if ($result->status() === 'skipped') {
                $output->writeln('Skipped: ' . $result->description());

                continue;
            }

            $failed = true;
            $message = $result->exception()?->getMessage() ?? 'Unknown error';
            $output->writeln('Failed: ' . $result->description() . ' (' . $message . ')');
        }

        return $failed ? ExitCode::FAILURE : ExitCode::SUCCESS;
    }
}
