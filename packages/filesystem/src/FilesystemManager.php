<?php

namespace Codemonster\Filesystem;

use Codemonster\Filesystem\Contracts\FilesystemInterface;
use Codemonster\Filesystem\Exceptions\FilesystemException;

class FilesystemManager
{
    /** @var array<string, mixed> */
    protected array $config;
    /** @var array<string, FilesystemInterface> */
    protected array $disks = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function defaultDisk(): string
    {
        $default = $this->config['default'] ?? 'local';

        return is_string($default) && $default !== '' ? $default : 'local';
    }

    public function disk(?string $name = null): FilesystemInterface
    {
        $name ??= $this->defaultDisk();

        if ($name === '') {
            throw new FilesystemException('Filesystem disk name cannot be empty.');
        }

        return $this->disks[$name] ??= $this->createDisk($name);
    }

    public function setDisk(string $name, FilesystemInterface $filesystem): void
    {
        if ($name === '') {
            throw new FilesystemException('Filesystem disk name cannot be empty.');
        }

        $this->disks[$name] = $filesystem;
    }

    /**
     * @return list<string>
     */
    public function disks(): array
    {
        $disks = $this->config['disks'] ?? [];

        if (!is_array($disks)) {
            return [];
        }

        return array_values(array_filter(array_keys($disks), 'is_string'));
    }

    protected function createDisk(string $name): FilesystemInterface
    {
        $config = $this->diskConfig($name);
        $driver = $config['driver'] ?? 'local';
        $driver = is_string($driver) && $driver !== '' ? $driver : 'local';

        if ($driver !== 'local') {
            throw new FilesystemException("Unsupported filesystem driver [{$driver}].");
        }

        $root = $config['root'] ?? null;
        if (!is_string($root) || $root === '') {
            throw new FilesystemException("Filesystem disk [{$name}] requires a root path.");
        }

        $url = $config['url'] ?? null;

        return new LocalFilesystem($root, is_string($url) && $url !== '' ? $url : null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function diskConfig(string $name): array
    {
        $disks = $this->config['disks'] ?? null;

        if (!is_array($disks) || !isset($disks[$name]) || !is_array($disks[$name])) {
            throw new FilesystemException("Filesystem disk [{$name}] is not configured.");
        }

        $config = [];
        foreach ($disks[$name] as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
