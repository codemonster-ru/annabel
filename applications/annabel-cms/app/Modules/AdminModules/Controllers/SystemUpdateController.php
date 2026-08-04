<?php

namespace Codemonster\Cms\Modules\AdminModules\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Cms\Modules\Core\ModuleManager;
use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Http\Request;
use Codemonster\Http\Response;

class SystemUpdateController
{
    private const DEFAULT_PER_PAGE = 10;

    /** @var list<int> */
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    public function __construct(
        private AdminScreenRendererInterface $admin,
        private ModuleManager $modules,
        private ModuleInstallationRegistry $installations,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.settings.system-updates');
    }

    public function data(Request $request): Response
    {
        $records = $this->installations->records() ?? [];
        $components = [];

        foreach ($this->modules->availableDefinitions() as $module) {
            if (!$module->system) {
                continue;
            }

            $record = $records[$module->name] ?? null;
            $components[] = [
                'name' => $module->name,
                'installed_version' => $record['installed_version'] ?? null,
                'available_version' => null,
            ];
        }

        usort(
            $components,
            static fn (array $left, array $right): int => strnatcasecmp($left['name'], $right['name']),
        );

        $page = $this->positiveInteger($request->query('page'), 1);
        $perPage = $this->positiveInteger($request->query('per_page'), self::DEFAULT_PER_PAGE);

        if (!in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $total = count($components);

        return Response::json([
            'cms_version' => (string) config('cms.version', 'unknown'),
            'latest_version' => null,
            'channel' => (string) config('cms.update_channel', 'stable'),
            'last_checked_at' => null,
            'last_successful_update_at' => null,
            'components' => array_slice($components, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'current_page' => $page,
            'per_page' => $perPage,
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
