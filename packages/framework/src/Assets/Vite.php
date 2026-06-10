<?php

namespace Codemonster\Annabel\Assets;

class Vite
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private string $basePath,
        private array $config = [],
    ) {
    }

    /**
     * @param string|list<string> $entries
     */
    public function render(string|array $entries): string
    {
        $entries = is_string($entries) ? [$entries] : $entries;

        if ($this->isRunningHot()) {
            return $this->renderHot($entries);
        }

        return $this->renderBuild($entries);
    }

    public function asset(string $entry): string
    {
        if ($this->isRunningHot()) {
            return rtrim($this->hotUrl(), '/') . '/' . ltrim($entry, '/');
        }

        $manifest = $this->manifest();
        $chunk = $manifest[$entry] ?? null;

        if (!is_array($chunk) || !is_string($chunk['file'] ?? null)) {
            throw new \RuntimeException("Vite entry [{$entry}] is missing from manifest.");
        }

        return $this->buildUrl((string) $chunk['file']);
    }

    private function isRunningHot(): bool
    {
        return is_file($this->hotFile());
    }

    /**
     * @param list<string> $entries
     */
    private function renderHot(array $entries): string
    {
        $tags = [
            '<script type="module" src="' . $this->escape(rtrim($this->hotUrl(), '/') . '/@vite/client') . '"></script>',
        ];

        foreach ($entries as $entry) {
            $tags[] = '<script type="module" src="' . $this->escape(rtrim($this->hotUrl(), '/') . '/' . ltrim($entry, '/')) . '"></script>';
        }

        return implode(PHP_EOL, $tags);
    }

    /**
     * @param list<string> $entries
     */
    private function renderBuild(array $entries): string
    {
        if (!$this->hasManifest()) {
            if ($this->strictManifest()) {
                throw new \RuntimeException("Vite manifest not found at [{$this->manifestPath()}].");
            }

            return '';
        }

        $manifest = $this->manifest();
        $tags = [];

        foreach ($entries as $entry) {
            $chunk = $manifest[$entry] ?? null;

            if (!is_array($chunk) || !is_string($chunk['file'] ?? null)) {
                throw new \RuntimeException("Vite entry [{$entry}] is missing from manifest.");
            }

            foreach ($this->cssFiles($chunk) as $css) {
                $tags[] = '<link rel="stylesheet" href="' . $this->escape($this->buildUrl($css)) . '">';
            }

            $file = (string) $chunk['file'];
            if (str_ends_with($file, '.css')) {
                $tags[] = '<link rel="stylesheet" href="' . $this->escape($this->buildUrl($file)) . '">';

                continue;
            }

            $tags[] = '<script type="module" src="' . $this->escape($this->buildUrl($file)) . '"></script>';
        }

        return implode(PHP_EOL, array_values(array_unique($tags)));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function manifest(): array
    {
        $path = $this->manifestPath();

        if (!is_file($path)) {
            throw new \RuntimeException("Vite manifest not found at [{$path}].");
        }

        $manifest = json_decode((string) file_get_contents($path), true);

        if (!is_array($manifest)) {
            throw new \RuntimeException("Vite manifest [{$path}] is invalid.");
        }

        $normalized = [];
        foreach ($manifest as $entry => $chunk) {
            if (is_string($entry) && is_array($chunk)) {
                $normalizedChunk = [];

                foreach ($chunk as $key => $value) {
                    if (is_string($key)) {
                        $normalizedChunk[$key] = $value;
                    }
                }

                $normalized[$entry] = $normalizedChunk;
            }
        }

        return $normalized;
    }

    private function hasManifest(): bool
    {
        return is_file($this->manifestPath());
    }

    private function strictManifest(): bool
    {
        $strict = $this->config['strict'] ?? null;

        if (is_bool($strict)) {
            return $strict;
        }

        if (is_string($strict)) {
            return in_array(strtolower($strict), ['1', 'true', 'yes', 'on'], true);
        }

        $environment = getenv('APP_ENV');

        return $environment === false || $environment === '' || $environment === 'production';
    }

    private function manifestPath(): string
    {
        return $this->basePath . '/' . trim($this->stringConfig('manifest', 'public/build/manifest.json'), '/');
    }

    private function hotFile(): string
    {
        return $this->basePath . '/' . trim($this->stringConfig('hot_file', 'public/hot'), '/');
    }

    private function hotUrl(): string
    {
        $url = trim((string) file_get_contents($this->hotFile()));

        return $url !== '' ? $url : $this->stringConfig('dev_server', 'http://localhost:5173');
    }

    private function buildUrl(string $file): string
    {
        return rtrim($this->stringConfig('build_url', '/build'), '/') . '/' . ltrim($file, '/');
    }

    /**
     * @param array<string, mixed> $chunk
     * @return list<string>
     */
    private function cssFiles(array $chunk): array
    {
        $css = $chunk['css'] ?? [];

        if (!is_array($css)) {
            return [];
        }

        return array_values(array_filter($css, 'is_string'));
    }

    private function stringConfig(string $key, string $default): string
    {
        $value = $this->config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
