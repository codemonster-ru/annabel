<?php

namespace Codemonster\Database\CLI;

interface CommandInterface
{
    /**
     * Command name, e.g. "migrate" or "migrate:rollback".
     */
    public function signature(): string;

    /**
     * Short description for help.
     */
    public function description(): string;

    /**
     * Handle command.
     *
     * @param list<string> $arguments Arguments after command name
     */
    public function handle(array $arguments): int;
}
