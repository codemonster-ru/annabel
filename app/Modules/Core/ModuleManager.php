<?php

namespace Codemonster\Xen\Modules\Core;

use Codemonster\Annabel\Contracts\ServiceProviderInterface;

class ModuleManager
{
    public function bootAll(array $exclude = []): void
    {
        $modulesPath = base_path('app/Modules');

        foreach (glob("$modulesPath/*/ModuleServiceProvider.php") as $file) {
            $class = $this->resolveNamespace($file);

            if (!$class || !class_exists($class)) {
                continue;
            }

            $moduleName = basename(dirname($file));

            if (in_array($moduleName, $exclude, true)) {
                continue;
            }

            $provider = new $class(app());

            if ($provider instanceof ServiceProviderInterface) {
                $provider->register();

                $moduleBase = dirname($file);
                $routesPath = $moduleBase . '/routes/web.php';

                if (file_exists($routesPath)) {
                    require_once $routesPath;
                }

                if (is_callable([$provider, 'boot'])) {
                    $provider->boot();
                }
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

        foreach (glob("$modulesPath/*/ModuleServiceProvider.php") as $file) {
            $class = $this->resolveNamespace($file);

            if ($class) {
                $result[basename(dirname($file))] = $class;
            }
        }

        return $result;
    }
}
