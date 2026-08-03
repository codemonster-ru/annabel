<?php

namespace Codemonster\Cms\Modules\Core\Services;

use Codemonster\Cms\Modules\Core\ModuleDefinition;
use Codemonster\Cms\Modules\Core\ModuleManager;

class ModuleActivationManager
{
    public function __construct(
        private ModuleManager $modules,
        private ModuleInstallationRegistry $installations,
    ) {
    }

    public function enable(string $name): void
    {
        $module = $this->module($name);
        $states = $this->states();

        if (!array_key_exists($name, $states)) {
            throw new \RuntimeException("Module is not installed: {$name}");
        }

        foreach ($module->dependencies as $dependency) {
            if (($states[$dependency] ?? false) !== true) {
                throw new \RuntimeException("Required module is not enabled: {$dependency}");
            }
        }

        $this->installations->enable($name);
    }

    /** @param array<string, bool> $states */
    public function canEnable(ModuleDefinition $module, array $states): bool
    {
        if ($module->system || ($states[$module->name] ?? null) !== false) {
            return false;
        }

        foreach ($module->dependencies as $dependency) {
            if (($states[$dependency] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    public function disable(string $name): void
    {
        $module = $this->module($name);
        $states = $this->states();

        if ($this->isSystem($module)) {
            throw new \RuntimeException("System module cannot be disabled: {$name}");
        }

        if (!array_key_exists($name, $states)) {
            throw new \RuntimeException("Module is not installed: {$name}");
        }

        foreach ($this->modules->availableDefinitions() as $dependent) {
            if (($states[$dependent->name] ?? false) === true && in_array($name, $dependent->dependencies, true)) {
                throw new \RuntimeException("Module is required by enabled module: {$dependent->name}");
            }
        }

        $this->installations->disable($name);
    }

    /** @param array<string, bool> $states */
    public function canDisable(ModuleDefinition $module, array $states): bool
    {
        if ($module->system || ($states[$module->name] ?? false) !== true) {
            return false;
        }

        foreach ($this->modules->availableDefinitions() as $dependent) {
            if (($states[$dependent->name] ?? false) === true
                && in_array($module->name, $dependent->dependencies, true)
            ) {
                return false;
            }
        }

        return true;
    }

    public function isSystem(ModuleDefinition $module): bool
    {
        return $module->system;
    }

    private function module(string $name): ModuleDefinition
    {
        $module = $this->modules->availableDefinitions()[$name] ?? null;

        if (!$module instanceof ModuleDefinition) {
            throw new \RuntimeException("Module not found: {$name}");
        }

        return $module;
    }

    /** @return array<string, bool> */
    private function states(): array
    {
        $states = $this->installations->states();

        if ($states === null) {
            throw new \RuntimeException('Module installation registry is not available.');
        }

        return $states;
    }
}
