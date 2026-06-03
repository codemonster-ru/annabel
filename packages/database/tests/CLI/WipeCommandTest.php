<?php

namespace Codemonster\Database\Tests\CLI;

use Codemonster\Database\CLI\Commands\WipeCommand;
use Codemonster\Database\Tests\TestCase;

class WipeCommandTest extends TestCase
{
    public function test_wipe_command_drops_all_tables_for_sqlite()
    {
        $connection = $this->fakeConnection();
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        $connection->results[$query] = [
            ['name' => 'migrations'],
            ['name' => 'posts'],
        ];

        $command = new WipeCommand($connection);

        $this->expectOutputString("Database wiped.\n");
        $this->assertSame(0, $command->handle(['--force']));

        $queries = $this->statementQueries($connection->log);

        $this->assertSame([
            'PRAGMA foreign_keys = OFF',
            'DROP TABLE IF EXISTS "migrations"',
            'DROP TABLE IF EXISTS "posts"',
            'PRAGMA foreign_keys = ON',
        ], $queries);
    }

    public function test_wipe_command_reports_when_nothing_to_wipe()
    {
        $connection = $this->fakeConnection();
        $query = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'";
        $connection->results[$query] = [];

        $command = new WipeCommand($connection);

        $this->expectOutputString("Nothing to wipe.\n");
        $this->assertSame(0, $command->handle(['--force']));
        $this->assertSame([], $this->statementQueries($connection->log));
    }

    private function statementQueries(array $log): array
    {
        $queries = [];

        foreach ($log as $entry) {
            if ($entry[0] === 'statement') {
                $queries[] = $entry[1];
            }
        }

        return $queries;
    }
}
