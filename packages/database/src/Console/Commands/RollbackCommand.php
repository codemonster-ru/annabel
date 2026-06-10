<?php

namespace Codemonster\Database\Console\Commands;

use Codemonster\Database\Console\CommandInterface;
use Codemonster\Database\Migrations\Migrator;

class RollbackCommand implements CommandInterface
{
    protected Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function signature(): string
    {
        return 'migrate:rollback';
    }

    public function description(): string
    {
        return 'Rollback last database migration batch';
    }

    public function handle(array $arguments): int
    {
        $step = $this->parseStepOption($arguments);
        $rolled = $this->migrator->rollback($step);

        if (empty($rolled)) {
            echo "Nothing to rollback.\n";

            return 0;
        }

        foreach ($rolled as $name) {
            echo "Rolled back: {$name}\n";
        }

        return 0;
    }

    /** @param list<string> $arguments */
    protected function parseStepOption(array $arguments): int
    {
        foreach ($arguments as $arg) {
            if (str_starts_with($arg, '--step=')) {
                $value = substr($arg, 7);

                return max(1, (int) $value);
            }
        }

        return 0;
    }
}
