<?php

namespace Codemonster\Env;

class EnvLoader implements LoaderInterface
{
    /** @var array<string, true> */
    private array $loaded = [];
    private ParserInterface $parser;

    public function __construct(?ParserInterface $parser = null)
    {
        $this->parser = $parser ?? new DefaultParser();
    }

    public function resetLoaded(): void
    {
        $this->loaded = [];
    }

    public function loadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false
    ): void {
        if (!is_file($path)) {
            throw new Exception\InvalidPathException("Env file not found: $path");
        }

        $content = $this->readFileContents($path, $maxBytes);

        $entries = $this->parser->parseStringRaw($content, $encoding);

        $this->applyEntries($entries, $strictResolve);
    }

    public function safeLoadFile(
        string $path,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false
    ): bool {
        if (!is_file($path)) {
            return false;
        }

        try {
            $this->loadFile($path, $encoding, $maxBytes, $strictResolve);
        } catch (Exception\InvalidFileException | Exception\InvalidPathException) {
            return false;
        }

        return true;
    }

    public function loadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null
    ): void {
        if ($maxBytes !== null) {
            if ($maxBytes < 0) {
                throw new Exception\InvalidFileException('Maximum content size cannot be negative.');
            }

            $length = strlen($content);

            if ($length > $maxBytes) {
                throw new Exception\InvalidFileException(
                    "Env content size exceeds {$maxBytes} bytes."
                );
            }
        }

        $entries = $this->parser->parseStringRaw($content, $encoding);

        $this->applyEntries($entries, $strictResolve);
    }

    public function safeLoadString(
        string $content,
        ?string $encoding = null,
        bool $strictResolve = false,
        ?int $maxBytes = null
    ): bool {
        try {
            $this->loadString($content, $encoding, $strictResolve, $maxBytes);
        } catch (Exception\InvalidFileException | Exception\InvalidPathException) {
            return false;
        }

        return true;
    }

    /** @param iterable<mixed> $paths */
    public function loadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null
    ): void {
        $loadedAny = false;

        foreach ($paths as $path) {
            if (!is_string($path)) {
                continue;
            }

            $expanded = $this->expandPath($path, $globFlags);

            if ($expanded !== null) {
                if ($expanded === []) {
                    if ($shortCircuit) {
                        continue;
                    }

                    throw new Exception\InvalidPathException("Env file not found: {$path}");
                }

                foreach ($expanded as $expandedPath) {
                    $this->loadFiles(
                        [$expandedPath],
                        $encoding,
                        $maxBytes,
                        $strictResolve,
                        $shortCircuit,
                        $globFlags
                    );

                    $loadedAny = true;

                    if ($shortCircuit) {
                        return;
                    }
                }

                continue;
            }

            if (!is_file($path)) {
                if ($shortCircuit) {
                    continue;
                }

                throw new Exception\InvalidPathException("Env file not found: {$path}");
            }

            $this->loadFile($path, $encoding, $maxBytes, $strictResolve);

            $loadedAny = true;

            if ($shortCircuit) {
                break;
            }
        }

        if (!$loadedAny) {
            throw new Exception\InvalidPathException('No env files could be loaded.');
        }
    }

    /** @param iterable<mixed> $paths */
    public function safeLoadFiles(
        iterable $paths,
        ?string $encoding = null,
        ?int $maxBytes = null,
        bool $strictResolve = false,
        bool $shortCircuit = true,
        ?int $globFlags = null
    ): bool {
        try {
            $this->loadFiles($paths, $encoding, $maxBytes, $strictResolve, $shortCircuit, $globFlags);
        } catch (Exception\InvalidFileException | Exception\InvalidPathException) {
            return false;
        }

        return true;
    }

    /** @return list<string>|null */
    private function expandPath(string $path, ?int $globFlags): ?array
    {
        if (!strpbrk($path, '*?[')) {
            return null;
        }

        $flags = $globFlags ?? (defined('GLOB_BRACE') ? constant('GLOB_BRACE') : 0);
        $matches = glob($path, $flags);

        if ($matches === false) {
            return [];
        }

        sort($matches, SORT_STRING);

        return $matches;
    }

    /** @param list<array{string, string|null, list<int>}> $entries */
    private function applyEntries(array $entries, bool $strictResolve): void
    {
        foreach ($entries as $entry) {
            [$name, $value, $vars] = $entry;

            if ($value === null) {
                $this->clearVariable($name);

                continue;
            }

            if ($strictResolve) {
                [$resolved, $unresolved] = $this->parser->expandVariablesWithReport($value, $vars);

                if ($unresolved !== []) {
                    $unique = array_values(array_unique($unresolved));
                    $missing = implode(', ', $unique);

                    throw new Exception\InvalidFileException(
                        "Unresolved variable reference(s) [{$missing}] in value for {$name}."
                    );
                }
            } else {
                $resolved = $this->parser->expandVariables($value, $vars);
            }

            if ($this->isExternallyDefined($name)) {
                continue;
            }

            $_ENV[$name] = $resolved;
            $_SERVER[$name] = $resolved;

            putenv("$name=$resolved");

            $this->loaded[$name] = true;
        }
    }

    private function readFileContents(string $path, ?int $maxBytes): string
    {
        if ($maxBytes !== null && $maxBytes < 0) {
            throw new Exception\InvalidFileException('Maximum file size cannot be negative.');
        }

        if ($maxBytes !== null) {
            $size = @filesize($path);

            if (is_int($size) && $size > $maxBytes) {
                throw new Exception\InvalidFileException(
                    "Env file size exceeds {$maxBytes} bytes: {$path}"
                );
            }
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw new Exception\InvalidPathException("Unable to read env file: $path");
        }

        $buffer = '';

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);

            if ($chunk === false) {
                fclose($handle);

                throw new Exception\InvalidPathException("Unable to read env file: $path");
            }

            if ($chunk === '') {
                continue;
            }

            $buffer .= $chunk;

            if ($maxBytes !== null && strlen($buffer) > $maxBytes) {
                fclose($handle);

                throw new Exception\InvalidFileException(
                    "Env file size exceeds {$maxBytes} bytes: {$path}"
                );
            }
        }

        fclose($handle);

        return $buffer;
    }

    private function isExternallyDefined(string $name): bool
    {
        if (isset($this->loaded[$name])) {
            return false;
        }

        return array_key_exists($name, $_ENV)
            || array_key_exists($name, $_SERVER)
            || getenv($name) !== false;
    }

    private function clearVariable(string $name): void
    {
        if ($this->isExternallyDefined($name)) {
            return;
        }

        unset($_ENV[$name], $_SERVER[$name]);
        putenv($name);
        unset($this->loaded[$name]);
    }
}
