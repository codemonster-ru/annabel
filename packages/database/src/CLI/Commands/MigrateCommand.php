<?php

namespace Codemonster\Database\CLI\Commands;

use Codemonster\Database\CLI\CommandInterface;
use Codemonster\Database\Migrations\Migrator;

class MigrateCommand implements CommandInterface
{
    protected Migrator $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function signature(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run all pending migrations';
    }

    public function handle(array $arguments): int
    {
        $executed = $this->migrator->migrate();

        if (empty($executed)) {
            echo "Nothing to migrate.\n";

            return 0;
        }

        foreach ($executed as $name) {
            echo "Migrated: {$name}\n";
        }

        return 0;
    }
}
