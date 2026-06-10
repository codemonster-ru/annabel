<?php

namespace Codemonster\Annabel\Console;

use InvalidArgumentException;

class CommandRegistry
{
    /**
     * @var list<class-string<Command>|Command>
     */
    protected array $commands = [];

    /**
     * @param class-string<Command>|Command|list<class-string<Command>|Command> $commands
     */
    public function add(string|Command|array $commands): void
    {
        $commands = is_array($commands) ? $commands : [$commands];

        foreach ($commands as $command) {
            if (
                !$command instanceof Command
                && !is_subclass_of($command, Command::class)
            ) {
                throw new InvalidArgumentException(
                    'Console commands must extend ' . Command::class . '.',
                );
            }

            if (!in_array($command, $this->commands, true)) {
                $this->commands[] = $command;
            }
        }
    }

    /**
     * @return list<class-string<Command>|Command>
     */
    public function all(): array
    {
        return $this->commands;
    }
}
