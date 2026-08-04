<?php

namespace Codemonster\Cms\Modules\AdminModules\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Core\Services\ModuleActivationManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Cms\Modules\Core\Services\ModuleInstaller;
use Codemonster\Cms\Modules\Core\Services\ModuleUpdater;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class ModuleListController
{
    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private ModuleManager $modules,
        private ModuleInstallationRegistry $installations,
        private ModuleActivationManager $activation,
        private ModuleInstaller $installer,
        private ModuleUpdater $updater,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.settings.modules');
    }

    public function data(Request $request): Response
    {
        $modules = [];
        $records = $this->installations->records() ?? [];
        $states = array_map(
            static fn (array $record): bool => $record['is_enabled'],
            $records,
        );

        foreach ($this->modules->availableDefinitions() as $module) {
            if ($module->system) {
                continue;
            }

            $record = $records[$module->name] ?? null;

            $modules[] = [
                'name' => $module->name,
                'version' => $module->version,
                'installed_version' => $record['installed_version'] ?? null,
                'dependencies' => $module->dependencies,
                'author' => $module->author,
                'is_installed' => array_key_exists($module->name, $states),
                'is_enabled' => $states[$module->name] ?? false,
                'can_enable' => $this->activation->canEnable($module, $states),
                'can_disable' => $this->activation->canDisable($module, $states),
                'can_install' => $this->installer->canInstall($module, $states),
                'can_uninstall' => $this->installer->canUninstall($module, $states),
                'can_update' => $this->updater->canUpdate($module, $records),
            ];
        }

        usort(
            $modules,
            static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']),
        );

        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $total = count($modules);

        return Response::json([
            'data' => array_slice($modules, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function enable(string $name): Response
    {
        return $this->changeState($name, true);
    }

    public function disable(string $name): Response
    {
        return $this->changeState($name, false);
    }

    public function install(string $name): Response
    {
        return $this->changeInstallation($name, true);
    }

    public function uninstall(string $name): Response
    {
        return $this->changeInstallation($name, false);
    }

    public function update(string $name): Response
    {
        try {
            $this->updater->update($name);
        } catch (\RuntimeException $exception) {
            return Response::json(['message' => $exception->getMessage()], 422);
        }

        return Response::json(['message' => 'Module updated successfully.']);
    }

    private function changeState(string $name, bool $enabled): Response
    {
        try {
            if ($enabled) {
                $this->activation->enable($name);
            } else {
                $this->activation->disable($name);
            }
        } catch (\RuntimeException $exception) {
            return Response::json(['message' => $exception->getMessage()], 422);
        }

        return Response::json([
            'message' => sprintf('Module %s successfully.', $enabled ? 'enabled' : 'disabled'),
        ]);
    }

    private function changeInstallation(string $name, bool $installed): Response
    {
        try {
            if ($installed) {
                $this->installer->install($name);
            } else {
                $this->installer->uninstall($name);
            }
        } catch (\RuntimeException $exception) {
            return Response::json(['message' => $exception->getMessage()], 422);
        }

        return Response::json([
            'message' => sprintf('Module %s successfully.', $installed ? 'installed' : 'uninstalled'),
        ]);
    }

    private function positiveInteger(mixed $value, int $default): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        return is_int($value) ? $value : $default;
    }
}
