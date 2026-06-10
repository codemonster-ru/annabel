<?php

namespace Codemonster\Database\Migrations;

class MigrationPathResolver
{
    /**
     * @var string[]
     */
    protected array $paths = [];

    /**
     * Add migrations path (only if directory exists).
     */
    public function addPath(string $path): void
    {
        // Allow registering paths even before the directory exists; it can be created later by CLI
        $this->paths[] = rtrim($path, DIRECTORY_SEPARATOR);

        // Keep list unique to avoid duplicate lookups
        $this->paths = array_values(array_unique($this->paths));
    }

    /**
     * @return string[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }
}
