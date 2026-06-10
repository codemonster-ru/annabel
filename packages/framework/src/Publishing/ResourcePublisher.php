<?php

namespace Codemonster\Annabel\Publishing;

use RuntimeException;

class ResourcePublisher
{
    public function __construct(protected string $basePath)
    {
    }

    /**
     * @param list<array{provider: class-string, source: string, destination: string, tags: list<string>}> $resources
     */
    public function publish(array $resources, bool $force = false): PublishResult
    {
        $published = [];
        $skipped = [];

        foreach ($resources as $resource) {
            if (is_link($resource['source'])) {
                throw new RuntimeException(
                    "Publish source [{$resource['source']}] cannot be a symbolic link.",
                );
            }

            $source = realpath($resource['source']);

            if ($source === false) {
                throw new RuntimeException("Publish source [{$resource['source']}] does not exist.");
            }

            $destination = $this->destination($resource['destination']);

            if (is_file($source)) {
                $this->copyFile($source, $destination, $force, $published, $skipped);

                continue;
            }

            if (!is_dir($source)) {
                throw new RuntimeException("Publish source [$source] must be a file or directory.");
            }

            $this->copyDirectory($source, $destination, $force, $published, $skipped);
        }

        return new PublishResult($published, $skipped);
    }

    protected function destination(string $destination): string
    {
        if (!$this->isAbsolutePath($destination)) {
            $destination = $this->basePath . DIRECTORY_SEPARATOR . $destination;
        }

        $base = realpath($this->basePath);

        if ($base === false) {
            throw new RuntimeException("Application base path [{$this->basePath}] does not exist.");
        }

        $base = $this->normalizePath($base);
        $destination = $this->normalizePath($destination);

        if ($destination !== $base && !str_starts_with($destination, $base . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException(
                "Publish destination [$destination] must be inside application base path [$base].",
            );
        }

        $this->assertNoSymlinkPath($base, $destination);

        return $destination;
    }

    /**
     * @param list<string> $published
     * @param list<string> $skipped
     */
    protected function copyFile(
        string $source,
        string $destination,
        bool $force,
        array &$published,
        array &$skipped,
    ): void {
        if (is_file($destination) && !$force) {
            $skipped[] = $destination;

            return;
        }

        $directory = dirname($destination);
        $this->ensureDirectory($directory);

        if (!copy($source, $destination)) {
            throw new RuntimeException("Unable to publish [$source] to [$destination].");
        }

        $published[] = $destination;
    }

    /**
     * @param list<string> $published
     * @param list<string> $skipped
     */
    protected function copyDirectory(
        string $source,
        string $destination,
        bool $force,
        array &$published,
        array &$skipped,
    ): void {
        $this->ensureDirectory($destination);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isLink()) {
                throw new RuntimeException("Publish source [{$item->getPathname()}] cannot be a symbolic link.");
            }

            $relative = substr($item->getPathname(), strlen($source) + 1);
            $target = $destination . DIRECTORY_SEPARATOR . $relative;

            if ($item->isDir()) {
                $this->ensureDirectory($target);
            } else {
                $this->copyFile($item->getPathname(), $target, $force, $published, $skipped);
            }
        }
    }

    protected function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create publish directory [$directory].");
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("Publish directory [$directory] is not writable.");
        }
    }

    protected function assertNoSymlinkPath(string $base, string $destination): void
    {
        $relative = ltrim(substr($destination, strlen($base)), DIRECTORY_SEPARATOR);
        $current = $base;

        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $segment) {
            if ($segment === '') {
                continue;
            }

            $current .= DIRECTORY_SEPARATOR . $segment;

            if (is_link($current)) {
                throw new RuntimeException(
                    "Publish destination [$destination] contains symbolic link [$current].",
                );
            }
        }
    }

    protected function normalizePath(string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $prefix = str_starts_with($path, DIRECTORY_SEPARATOR) ? DIRECTORY_SEPARATOR : '';
        $segments = [];

        foreach (explode(DIRECTORY_SEPARATOR, $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $segments);
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }
}
