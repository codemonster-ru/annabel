<?php

namespace Codemonster\Database\Tests\Console;

use Codemonster\Database\Console\Commands\SeedCommand;
use Codemonster\Database\Seeders\SeederRunner;
use Codemonster\Database\Tests\TestCase;

class SeedCommandTest extends TestCase
{
    public function test_seed_command_outputs_when_nothing_to_seed(): void
    {
        $runner = $this->createStub(SeederRunner::class);
        $runner->method('seed')->willReturn([]);

        $command = new SeedCommand($runner);

        $this->expectOutputString("Nothing to seed.\n");
        $this->assertSame(0, $command->handle([]));
    }

    public function test_seed_command_outputs_executed_seeders(): void
    {
        $runner = $this->createStub(SeederRunner::class);
        $runner->method('seed')->willReturn(['first_seed', 'second_seed']);

        $command = new SeedCommand($runner);

        $this->expectOutputString("Seeded: first_seed\nSeeded: second_seed\n");
        $this->assertSame(0, $command->handle([]));
    }
}
