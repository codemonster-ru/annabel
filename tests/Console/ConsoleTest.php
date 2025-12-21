<?php

use Codemonster\Annabel\Console\Console;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    public function test_default_command_prints_help(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel']));

        $this->assertStringContainsString('Annabel CLI', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_unknown_command_displays_error_and_help(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel', 'missing']));

        $this->assertStringContainsString('Unknown command: missing', $output);
        $this->assertStringContainsString('Available commands:', $output);
    }

    public function test_help_alias_is_resolved(): void
    {
        $console = new Console();
        $output = $this->captureOutput(fn() => $console->run(['annabel', 'help']));

        $this->assertStringContainsString('Annabel CLI', $output);
        $this->assertStringContainsString('list', $output);
    }

    public function test_registered_commands_include_database_commands(): void
    {
        $console = new Console();
        $commandNames = array_keys($console->getCommands());
        $expected = [
            'list',
            'about',
            'route:list',
            'config:get',
            'container:list',
            'serve',
            'make:migration',
            'migrate',
            'migrate:rollback',
            'migrate:status',
            'make:seed',
            'seed',
            'db:wipe',
            'db:truncate',
        ];

        foreach ($expected as $name) {
            $this->assertContains($name, $commandNames, "Missing command [{$name}].");
        }
    }

    public function test_registered_commands_match_expected_list(): void
    {
        $console = new Console();
        $commandNames = array_keys($console->getCommands());
        $expected = [
            'list',
            'about',
            'route:list',
            'config:get',
            'container:list',
            'serve',
            'make:migration',
            'migrate',
            'migrate:rollback',
            'migrate:status',
            'make:seed',
            'seed',
            'db:wipe',
            'db:truncate',
        ];

        sort($commandNames);
        sort($expected);

        $this->assertSame($expected, $commandNames);
    }

    public function test_command_aliases_are_registered(): void
    {
        $console = new Console();

        $this->assertSame(['help'], $console->getAliasesFor('list'));
        $this->assertSame([], $console->getAliasesFor('about'));
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();

        return ob_get_clean() ?: '';
    }
}
