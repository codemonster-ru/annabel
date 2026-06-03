<?php

namespace Codemonster\Database\Seeders;

use Codemonster\Database\Contracts\ConnectionInterface;

class SeederRunner
{
    protected ConnectionInterface $connection;

    protected SeedPathResolver $paths;

    public function __construct(ConnectionInterface $connection, SeedPathResolver $paths)
    {
        $this->connection = $connection;
        $this->paths = $paths;
    }

    /**
     * Run all seeders.
     *
     * @return string[] List of executed seeder names
     */
    public function seed(): array
    {
        $files = $this->getSeedFiles();

        if (empty($files)) {
            return [];
        }

        $executed = [];

        foreach ($files as $name => $file) {
            $instance = $this->resolveSeeder($file);

            $this->connection->transaction(function () use ($instance, $name, &$executed) {
                $instance->run();
                $executed[] = $name;
            });
        }

        return $executed;
    }

    /**
     * @return array<string,string> [seederName => filePath]
     */
    public function getSeedFiles(): array
    {
        $files = [];

        foreach ($this->paths->getPaths() as $path) {
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $name = basename($file, '.php');
                $files[$name] = $file;
            }
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * Load seeder instance from file.
     */
    protected function resolveSeeder(string $file): Seeder
    {
        $seeder = require $file;

        if (!$seeder instanceof Seeder) {
            throw new \RuntimeException("Seeder file [$file] must return instance of " . Seeder::class);
        }

        return $seeder;
    }
}
