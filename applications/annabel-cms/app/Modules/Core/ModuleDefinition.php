<?php

namespace Codemonster\Cms\Modules\Core;

final class ModuleDefinition
{
    /**
     * @param array<int, string> $dependencies
     * @param array<string, mixed> $assets
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $path,
        public readonly array $dependencies = [],
        public readonly ?string $provider = null,
        public readonly ?string $routes = null,
        public readonly ?string $views = null,
        public readonly array $assets = [],
    ) {
    }

    public function resolve(?string $relativePath): ?string
    {
        if ($relativePath === null || $relativePath === '') {
            return null;
        }

        return $this->path . '/' . ltrim($relativePath, '/');
    }
}
