<?php

namespace Codemonster\Cms\Support\Assets;

final class ViteAssetManifest
{
    private const UNRESOLVED_IMPORT_NEEDLE = '@codemonster-ru/';

    public function __construct(
        private string $publicPath,
        private string $publicUrl,
        private string $entry,
        private ?string $faviconEntry = null,
    ) {
        $this->publicPath = rtrim($this->publicPath, '/');
        $this->publicUrl = '/' . trim($this->publicUrl, '/');
    }

    /**
     * @return array{script: string, styles: list<string>, favicon: string|null}
     */
    public function entrypoints(string $missingMessage, string $invalidMessage): array
    {
        $manifest = $this->manifest($missingMessage);
        $entry = $manifest[$this->entry] ?? null;

        if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
            throw new \RuntimeException($invalidMessage);
        }

        $scriptFile = $this->publicPath . '/' . ltrim($entry['file'], '/');

        if (!$this->isValidBundle($scriptFile)) {
            throw new \RuntimeException($invalidMessage);
        }

        return [
            'script' => $this->url($entry['file']),
            'styles' => $this->styles($entry),
            'favicon' => $this->favicon($manifest),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(string $missingMessage): array
    {
        $manifestPath = $this->publicPath . '/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            throw new \RuntimeException($missingMessage);
        }

        $contents = file_get_contents($manifestPath);
        $manifest = is_string($contents) ? json_decode($contents, true) : null;

        if (!is_array($manifest)) {
            throw new \RuntimeException("Vite manifest is invalid: {$manifestPath}");
        }

        return $manifest;
    }

    /**
     * @param array<string, mixed> $entry
     * @return list<string>
     */
    private function styles(array $entry): array
    {
        $styles = [];

        foreach ((array) ($entry['css'] ?? []) as $css) {
            if (is_string($css)) {
                $styles[] = $this->url($css);
            }
        }

        return $styles;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function favicon(array $manifest): ?string
    {
        if ($this->faviconEntry === null) {
            return null;
        }

        $entry = $manifest[$this->faviconEntry] ?? null;
        $file = is_array($entry) ? ($entry['file'] ?? null) : null;

        return is_string($file) && $file !== ''
            ? $this->url($file)
            : null;
    }

    private function url(string $file): string
    {
        return $this->publicUrl . '/' . ltrim($file, '/');
    }

    private function isValidBundle(string $bundlePath): bool
    {
        if (!is_file($bundlePath)) {
            return false;
        }

        $contents = file_get_contents($bundlePath);

        return is_string($contents)
            && !str_contains($contents, self::UNRESOLVED_IMPORT_NEEDLE);
    }
}
