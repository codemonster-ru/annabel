<?php

namespace Codemonster\Annabel\Bootstrap;

use Closure;
use Composer\InstalledVersions;
use RuntimeException;
use Throwable;

class PackageManifest
{
    protected Closure $packagePaths;

    public function __construct(
        protected string $basePath,
        ?Closure $packagePaths = null
    ) {
        $this->packagePaths = $packagePaths ?? fn(): array => $this->installedPackagePaths();
    }

    /**
     * @param list<string> $dontDiscover
     * @return list<string>
     */
    public function providers(
        array $dontDiscover = [],
        bool $useCache = true,
        ?string $cachePath = null
    ): array {
        if (in_array('*', $dontDiscover, true)) {
            return [];
        }

        $packagePaths = ($this->packagePaths)();

        if (!is_array($packagePaths)) {
            throw new RuntimeException('Package discovery source must return an array.');
        }

        $packagePaths = $this->normalizePackagePaths($packagePaths);

        ksort($packagePaths);

        $fingerprint = $this->fingerprint($packagePaths, $dontDiscover);
        $cachePath ??= $this->basePath . '/bootstrap/cache/packages.php';

        if ($useCache) {
            $cached = $this->readCache($cachePath, $fingerprint);

            if ($cached !== null) {
                return $cached;
            }
        }

        $providers = $this->discover($packagePaths, $dontDiscover);

        if ($useCache) {
            $this->writeCache($cachePath, $fingerprint, $providers);
        }

        return $providers;
    }

    /**
     * @return array<string, string>
     */
    protected function installedPackagePaths(): array
    {
        if (!class_exists(InstalledVersions::class)) {
            return [];
        }

        $paths = [];

        foreach (InstalledVersions::getInstalledPackages() as $package) {
            try {
                $path = InstalledVersions::getInstallPath($package);
            } catch (Throwable) {
                continue;
            }

            if (is_string($path) && $path !== '') {
                $paths[$package] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param array<string, string> $packagePaths
     * @param list<string> $dontDiscover
     * @return list<string>
     */
    protected function discover(array $packagePaths, array $dontDiscover): array
    {
        $providers = [];

        foreach ($packagePaths as $package => $path) {
            if (in_array($package, $dontDiscover, true)) {
                continue;
            }

            $composerFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';

            if (!is_file($composerFile)) {
                continue;
            }

            try {
                $composer = json_decode(
                    (string) file_get_contents($composerFile),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                throw new RuntimeException(
                    "Invalid composer.json for discovered package [$package].",
                    previous: $e
                );
            }

            $extra = is_array($composer) ? ($composer['extra'] ?? null) : null;
            $annabel = is_array($extra) ? ($extra['annabel'] ?? null) : null;
            $declared = is_array($annabel) ? ($annabel['providers'] ?? []) : [];

            if (is_string($declared)) {
                $declared = [$declared];
            }

            if (!is_array($declared)) {
                throw new RuntimeException(
                    "Package [$package] extra.annabel.providers must be a string or array."
                );
            }

            foreach ($declared as $provider) {
                if (!is_string($provider) || $provider === '') {
                    throw new RuntimeException(
                        "Package [$package] contains an invalid Annabel provider declaration."
                    );
                }

                $providers[] = $provider;
            }
        }

        return array_values(array_unique($providers));
    }

    /**
     * @param array<string, string> $packagePaths
     * @param list<string> $dontDiscover
     */
    protected function fingerprint(array $packagePaths, array $dontDiscover): string
    {
        $metadata = ['packages' => [], 'dont_discover' => $dontDiscover];

        foreach ($packagePaths as $package => $path) {
            $composerFile = rtrim((string) $path, DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR
                . 'composer.json';

            $metadata['packages'][$package] = [
                'path' => $composerFile,
                'hash' => is_file($composerFile) ? hash_file('sha256', $composerFile) : null,
            ];
        }

        return hash('sha256', serialize($metadata));
    }

    /**
     * @return list<string>|null
     */
    protected function readCache(string $cachePath, string $fingerprint): ?array
    {
        if (!is_file($cachePath)) {
            return null;
        }

        $cached = require $cachePath;

        if (
            !is_array($cached)
            || ($cached['fingerprint'] ?? null) !== $fingerprint
            || !isset($cached['providers'])
            || !is_array($cached['providers'])
        ) {
            return null;
        }

        $providers = $cached['providers'];

        if (!array_is_list($providers)) {
            return null;
        }

        foreach ($providers as $provider) {
            if (!is_string($provider)) {
                return null;
            }
        }

        /** @var list<string> $providers */
        return $providers;
    }

    /**
     * @param array<mixed, mixed> $paths
     * @return array<string, string>
     */
    private function normalizePackagePaths(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $package => $path) {
            if (!is_string($package) || !is_string($path)) {
                throw new RuntimeException('Package discovery paths must be a string map.');
            }
            $normalized[$package] = $path;
        }

        return $normalized;
    }

    /**
     * @param list<string> $providers
     */
    protected function writeCache(string $cachePath, string $fingerprint, array $providers): void
    {
        $directory = dirname($cachePath);

        if (!is_dir($directory) && !@mkdir($directory, 0770, true) && !is_dir($directory)) {
            return;
        }

        if (!is_writable($directory)) {
            return;
        }

        $contents = "<?php\n\nreturn " . var_export([
            'fingerprint' => $fingerprint,
            'providers' => $providers,
        ], true) . ";\n";
        $temporary = $cachePath . '.' . bin2hex(random_bytes(6)) . '.tmp';

        if (@file_put_contents($temporary, $contents, LOCK_EX) === false) {
            return;
        }

        if (!@rename($temporary, $cachePath)) {
            @unlink($temporary);
        }
    }
}
