<?php

namespace Codemonster\Xen\Modules\Core;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;

class ModuleManager
{
    public function bootAll(array $exclude = []): void
    {
        $modulesPath = base_path('app/Modules');

        foreach (glob("$modulesPath/*", GLOB_ONLYDIR) as $moduleDir) {
            $moduleName = basename($moduleDir);
            
            if (in_array($moduleName, $exclude, true)) {
                continue;
            }

            $providerFile = $moduleDir . '/ModuleServiceProvider.php';
            $provider = null;

            if (file_exists($providerFile)) {
                $class = $this->resolveNamespace($providerFile);

                if ($class && class_exists($class)) {
                    $instance = new $class(app());

                    if ($instance instanceof ServiceProviderInterface) {
                        $provider = $instance;
                        $provider->register();
                    }
                }
            }

            $routesPath = $moduleDir . '/routes/web.php';

            if ($provider) {
                if (file_exists($routesPath)) {
                    require_once $routesPath;
                }

                if (is_callable([$provider, 'boot'])) {
                    $provider->boot();
                }

                continue;
            }

            $viewsPath = $moduleDir . '/views';

            if (is_dir($viewsPath)) {
                view()->addNamespace(strtolower($moduleName), $viewsPath);
            }

            if (file_exists($routesPath)) {
                require_once $routesPath;
            }
        }
    }

    protected function resolveNamespace(string $file): ?string
    {
        $contents = file_get_contents($file);

        preg_match('/namespace\s+([^;]+);/', $contents, $nsMatch);
        preg_match('/class\s+([a-zA-Z0-9_]+)/', $contents, $classMatch);

        if (!isset($nsMatch[1], $classMatch[1])) {
            return null;
        }

        return trim($nsMatch[1]) . '\\' . trim($classMatch[1]);
    }

    public function listAll(): array
    {
        $result = [];
        $modulesPath = app()->getBasePath() . '/app/Modules';

        foreach (glob("$modulesPath/*", GLOB_ONLYDIR) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $providerFile = $moduleDir . '/ModuleServiceProvider.php';
            $class = null;

            if (file_exists($providerFile)) {
                $class = $this->resolveNamespace($providerFile);
            }

            $result[$moduleName] = $class ?: '(auto)';
        }

        return $result;
    }
}
