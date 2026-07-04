<?php

namespace Codemonster\Cms\Modules\Setup\Services;

class EnvironmentFile
{
    public function __construct(
        private string $path,
    ) {
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function write(array $values): void
    {
        $contents = is_file($this->path)
            ? (string) file_get_contents($this->path)
            : '';

        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $line = $key . '=' . $this->format($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $line, $contents, 1);
            } else {
                $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
            }

            $stringValue = $value === null ? '' : (string) $value;
            putenv($key . '=' . $stringValue);
            $_ENV[$key] = $stringValue;
            $_SERVER[$key] = $stringValue;
        }

        file_put_contents($this->path, ltrim($contents, PHP_EOL));
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
            return '"' . addcslashes($string, "\\\"") . '"';
        }

        return $string;
    }
}
