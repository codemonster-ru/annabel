<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Scheduler\Schedule;
use Codemonster\Scheduler\ScheduledTask;

class ScheduleListCommand extends Command
{
    public function getName(): string
    {
        return 'schedule:list';
    }

    public function getAliases(): array
    {
        return ['schedules'];
    }

    public function getDescription(): string
    {
        return 'Show registered scheduled tasks.';
    }

    public function getUsage(): string
    {
        return 'schedule:list';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var Schedule $schedule */
        $schedule = $this->console()->getApplication()->make(Schedule::class);
        $tasks = $schedule->tasks();

        if ($tasks === []) {
            $output->writeln('No scheduled tasks registered.');

            return ExitCode::SUCCESS;
        }

        $output->writeln('Scheduled tasks:');
        $output->writeln(sprintf(
            '  %s  %s  %s  %s',
            str_pad('Expression', 19),
            str_pad('Description', 30),
            str_pad('Due', 5),
            'Overlap',
        ));

        $now = new \DateTimeImmutable();

        foreach ($tasks as $task) {
            $output->writeln(sprintf(
                '  %s  %s  %s  %s',
                str_pad($this->cronExpression($task), 19),
                str_pad($task->description(), 30),
                str_pad($task->isDue($now) ? 'yes' : 'no', 5),
                $this->overlapStatus($task),
            ));
        }

        return ExitCode::SUCCESS;
    }

    protected function cronExpression(ScheduledTask $task): string
    {
        $expression = $task->expression();

        return implode(' ', [
            $this->field($expression['minutes'], 0, 59),
            $this->field($expression['hours'], 0, 23),
            $this->field($expression['days_of_month'], 1, 31),
            $this->field($expression['months'], 1, 12),
            $this->field($expression['weekdays'], 0, 6),
        ]);
    }

    /**
     * @param list<int> $values
     */
    protected function field(array $values, int $min, int $max): string
    {
        if ($values === range($min, $max)) {
            return '*';
        }

        return implode(',', $values);
    }

    protected function overlapStatus(ScheduledTask $task): string
    {
        if (!$task->preventsOverlaps()) {
            return '-';
        }

        return 'locked ' . $task->overlapExpiresAfter() . 's';
    }
}
