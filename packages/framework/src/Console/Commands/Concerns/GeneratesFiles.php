<?php

namespace Codemonster\Annabel\Console\Commands\Concerns;

use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;

trait GeneratesFiles
{
    protected function generateClass(
        string $name,
        string $baseNamespace,
        string $baseDirectory,
        string $suffix,
        callable $stub,
        OutputInterface $output,
        bool $force = false,
    ): int {
        $class = $this->normalizeClassName($name, $suffix);

        if ($class === null) {
            $output->writeln("Invalid class name [{$name}]. Use StudlyCase with optional namespace separators.");

            return ExitCode::INVALID;
        }

        $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        $path = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($path) && !$force) {
            $output->writeln("File already exists: {$path}");

            return ExitCode::FAILURE;
        }

        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            $output->writeln("Cannot create directory: {$directory}");

            return ExitCode::FAILURE;
        }

        $namespace = trim($baseNamespace . '\\' . trim(str_replace('/', '\\', dirname(str_replace('\\', '/', $class))), '.\\'), '\\');
        $shortClass = basename(str_replace('\\', '/', $class));

        file_put_contents($path, $stub($namespace, $shortClass));
        $output->writeln("Created: {$path}");

        return ExitCode::SUCCESS;
    }

    protected function normalizeClassName(string $name, string $suffix): ?string
    {
        $name = trim(str_replace('/', '\\', $name), '\\');

        if ($name === '') {
            return null;
        }

        $segments = explode('\\', $name);
        foreach ($segments as &$segment) {
            if ($segment === '' || !preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $segment)) {
                return null;
            }

            $segment = ucfirst($segment);
        }
        unset($segment);

        $class = array_pop($segments);

        if (!str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        $segments[] = $class;

        return implode('\\', $segments);
    }
}
