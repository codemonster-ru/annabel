<?php

namespace Codemonster\Env;

use Codemonster\Env\Exceptions\InvalidFileException;
use Codemonster\Env\Exceptions\InvalidPathException;

class EnvFileWriter
{
    /**
     * @param array<string, scalar|null> $values
     */
    public function write(string $path, array $values, bool $updateProcess = true): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            throw new InvalidPathException("Environment directory does not exist: {$directory}");
        }

        if ((is_file($path) && !is_writable($path)) || (!is_file($path) && !is_writable($directory))) {
            throw new InvalidPathException("Environment file is not writable: {$path}");
        }

        $contents = is_file($path) ? file_get_contents($path) : '';

        if (!is_string($contents)) {
            throw new InvalidPathException("Unable to read environment file: {$path}");
        }

        $normalized = $this->normalizeValues($values);
        $written = $this->replaceEntries($contents, $normalized);

        $this->writeFile($path, $written);

        if ($updateProcess) {
            $this->updateProcess($normalized);
        }
    }

    /**
     * @param array<string, scalar|null> $values
     * @return array<string, scalar|null>
     */
    private function normalizeValues(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || !$this->validName($key)) {
                throw new InvalidFileException("Invalid environment variable name: {$key}");
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    /**
     * @param array<string, scalar|null> $values
     */
    private function replaceEntries(string $contents, array $values): string
    {
        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $lines = $contents === '' ? [] : preg_split('/\r\n|\n|\r/', $contents);

        if ($lines === false) {
            throw new InvalidFileException('Unable to split environment file into lines.');
        }

        $pending = $values;
        $output = [];

        $lastIndex = array_key_last($lines);

        foreach ($lines as $index => $line) {
            if ($index === $lastIndex && $line === '') {
                continue;
            }

            $name = $this->lineName($line);

            if ($name !== null && array_key_exists($name, $pending)) {
                $output[] = $name . '=' . $this->format($pending[$name]);
                unset($pending[$name]);
                continue;
            }

            $output[] = $line;
        }

        foreach ($pending as $name => $value) {
            $output[] = $name . '=' . $this->format($value);
        }

        return implode($lineEnding, $output) . $lineEnding;
    }

    private function lineName(string $line): ?string
    {
        if (preg_match('/^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_.]*)\s*=/', $line, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function validName(string $name): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $name) === 1;
    }

    private function format(string|int|float|bool|null $value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $string = (string) $value;

        if ($string === '') {
            return '""';
        }

        if (preg_match('/\s|#|=|"/', $string) === 1) {
            return '"' . addcslashes($string, '\\"') . '"';
        }

        return $string;
    }

    private function writeFile(string $path, string $contents): void
    {
        $temporary = tempnam(dirname($path), 'env-write-');

        if ($temporary === false) {
            throw new InvalidPathException("Unable to create temporary environment file near: {$path}");
        }

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
                throw new InvalidPathException("Unable to write temporary environment file: {$temporary}");
            }

            if (!rename($temporary, $path)) {
                throw new InvalidPathException("Unable to replace environment file: {$path}");
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * @param array<string, scalar|null> $values
     */
    private function updateProcess(array $values): void
    {
        foreach ($values as $key => $value) {
            $stringValue = $value === null ? '' : (string) $value;
            putenv($key . '=' . $stringValue);
            $_ENV[$key] = $stringValue;
            $_SERVER[$key] = $stringValue;
        }
    }
}
