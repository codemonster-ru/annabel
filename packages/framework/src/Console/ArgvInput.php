<?php

namespace Codemonster\Annabel\Console;

use Codemonster\Annabel\Console\Contracts\InputInterface;

class ArgvInput implements InputInterface
{
    protected string $command;
    /** @var list<string> */
    protected array $tokens = [];
    /** @var list<string> */
    protected array $arguments = [];
    /** @var array<string, string|bool> */
    protected array $options = [];

    /**
     * @param list<string> $argv
     */
    public function __construct(array $argv, string $defaultCommand = 'list')
    {
        $this->command = $argv[1] ?? $defaultCommand;
        $tokens = array_slice($argv, 2);
        $this->tokens = $tokens;

        for ($index = 0, $count = count($tokens); $index < $count; $index++) {
            $token = $tokens[$index];

            if (!str_starts_with($token, '--')) {
                $this->arguments[] = $token;

                continue;
            }

            $option = substr($token, 2);

            if ($option === '') {
                continue;
            }

            if (str_contains($option, '=')) {
                [$name, $value] = explode('=', $option, 2);
                $this->options[$name] = $value;

                continue;
            }

            $next = $tokens[$index + 1] ?? null;

            if (is_string($next) && !str_starts_with($next, '--')) {
                $this->options[$option] = $next;
                $index++;
            } else {
                $this->options[$option] = true;
            }
        }
    }

    public function command(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function arguments(): array
    {
        return $this->arguments;
    }

    public function tokens(): array
    {
        return $this->tokens;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    public function option(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }
}
