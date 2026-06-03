<?php

namespace Codemonster\Database\CLI;

class CommandRegistry
{
    /**
     * @var array<string,CommandInterface>
     */
    protected array $commands = [];

    public function register(CommandInterface $command): void
    {
        $this->commands[$command->signature()] = $command;
    }

    /**
     * @return CommandInterface[]
     */
    public function all(): array
    {
        return array_values($this->commands);
    }

    public function get(string $signature): ?CommandInterface
    {
        return $this->commands[$signature] ?? null;
    }

    public function dispatch(array $argv): int
    {
        if (count($argv) < 2) {
            $this->printHelp();

            return 1;
        }

        $name = $argv[1];
        $arguments = array_slice($argv, 2);

        $command = $this->get($name);

        if (!$command) {
            fwrite(STDERR, "Command [$name] not found.\n");

            $this->printHelp();

            return 1;
        }

        return $command->handle($arguments);
    }

    protected function printHelp(): void
    {
        echo "Available commands:\n";

        foreach ($this->commands as $cmd) {
            echo sprintf(
                "  %-20s %s\n",
                $cmd->signature(),
                $cmd->description()
            );
        }
    }
}
