<?php

declare(strict_types=1);

namespace Codemonster\Database\Tests\Console;

use Codemonster\Database\Console\Commands\TruncateCommand;
use Codemonster\Database\Tests\TestCase;

class TruncateCommandTest extends TestCase
{
    public function test_truncate_command_cleans_tables_except_migrations_for_sqlite(): void
    {
        $connection = $this->fakeConnection();
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        $connection->results[$query] = [
            ['name' => 'migrations'],
            ['name' => 'users'],
        ];

        $command = new TruncateCommand($connection);

        $this->expectOutputString("Database cleaned.\n");
        $this->assertSame(0, $command->handle(['--force']));

        $queries = $this->statementQueries($connection->log);

        $this->assertSame([
            'PRAGMA foreign_keys = OFF',
            'DELETE FROM "users"',
            'PRAGMA foreign_keys = ON',
        ], $queries);
    }

    public function test_truncate_command_reports_when_nothing_to_clean(): void
    {
        $connection = $this->fakeConnection();
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        $connection->results[$query] = [];

        $command = new TruncateCommand($connection);

        $this->expectOutputString("Nothing to clean.\n");
        $this->assertSame(0, $command->handle(['--force']));
        $this->assertSame([], $this->statementQueries($connection->log));
    }

    /**
     * @param list<array{0: string, 1?: string, 2?: array<int|string, mixed>}> $log
     * @return list<string>
     */
    private function statementQueries(array $log): array
    {
        $queries = [];

        foreach ($log as $entry) {
            if ($entry[0] === 'statement') {
                $queries[] = $entry[1] ?? '';
            }
        }

        return $queries;
    }
}
