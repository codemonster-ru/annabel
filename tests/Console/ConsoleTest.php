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

    private function captureOutput(callable $callback): string
    {
        ob_start();
        $callback();

        return ob_get_clean() ?: '';
    }
}
