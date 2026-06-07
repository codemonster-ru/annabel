<?php

namespace Codemonster\View\Locator;

use RuntimeException;

final class DefaultLocator implements LocatorInterface
{
    /** @var array<string, list<string>> */
    private array $paths = ['' => []];

    /** @param string|list<string> $paths */
    public function __construct(array|string $paths = [])
    {
        foreach ((array) $paths as $p) {
            $this->addPath($p);
        }

        if ($this->paths[''] === []) {
            throw new \RuntimeException('At least one base path is required.');
        }
    }

    public function addPath(string $path, string $namespace = ''): void
    {
        if (!is_dir($path)) {
            return;
        }

        $real = realpath($path);

        if ($real === false) {
            return;
        }

        $this->paths[$namespace] ??= [];
        $this->paths[$namespace][] = rtrim($real, DIRECTORY_SEPARATOR);
    }

    /** @param string|list<string> $extensions */
    public function resolve(string $name, string|array $extensions): string
    {
        $extensions = (array) $extensions;

        [$ns, $short] = $this->splitNs($name);

        if (str_contains($short, '..')) {
            throw new RuntimeException('Path traversal detected.');
        }

        $rel = str_replace('.', DIRECTORY_SEPARATOR, trim($short));

        $stacks = $this->pathsLookup($ns);

        foreach ($stacks as $base) {
            foreach ($extensions as $ext) {
                $candidate = $base . DIRECTORY_SEPARATOR . $rel . '.' . $ext;
                $real = @realpath($candidate);

                if ($real && str_starts_with($real, $base . DIRECTORY_SEPARATOR) && is_file($real)) {
                    return $real;
                }
            }
        }

        throw new RuntimeException("View not found: {$name}");
    }

    /** @return array{string, string} */
    private function splitNs(string $name): array
    {
        $pos = strpos($name, '::');

        if ($pos === false) {
            return ['', $name];
        }

        return [substr($name, 0, $pos), substr($name, $pos + 2)];
    }

    /** @return list<string> */
    private function pathsLookup(string $ns): array
    {
        $out = [];

        if ($ns !== '' && !empty($this->paths[$ns])) {
            $out = array_merge($out, $this->paths[$ns]);
        }

        if (!empty($this->paths[''])) {
            $out = array_merge($out, $this->paths['']);
        }

        return $out;
    }
}
