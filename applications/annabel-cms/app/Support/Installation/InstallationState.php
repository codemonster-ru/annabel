<?php

namespace Codemonster\Cms\Support\Installation;

class InstallationState
{
    public function __construct(
        private string $path,
    ) {
    }

    public function isInstalled(): bool
    {
        if (!is_file($this->path)) {
            return false;
        }

        $contents = file_get_contents($this->path);
        $data = is_string($contents) ? json_decode($contents, true) : null;

        return is_array($data)
            && is_string($data['installed_at'] ?? null)
            && ($data['installed_at'] ?? '') !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function data(): ?array
    {
        if (!is_file($this->path)) {
            return null;
        }

        $contents = file_get_contents($this->path);
        $data = is_string($contents) ? json_decode($contents, true) : null;

        return is_array($data) ? $data : null;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function markInstalled(array $data = []): void
    {
        $payload = array_merge($data, [
            'installed_at' => date(DATE_ATOM),
        ]);

        $directory = dirname($this->path);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $this->path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
        );
    }
}
