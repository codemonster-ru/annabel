<?php

namespace Codemonster\Database\Console\Commands;

use Codemonster\Database\Console\CommandInterface;
use Codemonster\Database\Seeders\SeederRunner;

class SeedCommand implements CommandInterface
{
    protected SeederRunner $seeder;

    public function __construct(SeederRunner $seeder)
    {
        $this->seeder = $seeder;
    }

    public function signature(): string
    {
        return 'seed';
    }

    public function description(): string
    {
        return 'Run all seeders';
    }

    public function handle(array $arguments): int
    {
        $executed = $this->seeder->seed();

        if (empty($executed)) {
            echo "Nothing to seed.\n";

            return 0;
        }

        foreach ($executed as $name) {
            echo "Seeded: {$name}\n";
        }

        return 0;
    }
}
