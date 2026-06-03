<?php

namespace Codemonster\Annabel\Console;

abstract class Command
{
    protected ?Console $console = null;

    /**
     * Unique command name (e.g. "list" or "make:controller").
     */
    abstract public function getName(): string;

    /**
     * Optional command aliases (e.g. ["help"] for "list").
     *
     * @return string[]
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * Short, single-line description shown in the help output.
     */
    abstract public function getDescription(): string;

    /**
     * Usage string that will be displayed with the command help.
     */
    public function getUsage(): string
    {
        return $this->getName();
    }

    public function setConsole(Console $console): void
    {
        $this->console = $console;
    }

    protected function console(): Console
    {
        if (!$this->console) {
            throw new \RuntimeException('Console instance is not assigned to the command.');
        }

        return $this->console;
    }

    /**
     * Execute the command.
     *
     * @param array<int, string> $arguments
     */
    public function handle(array $arguments = []): int
    {
        return 0;
    }
}
