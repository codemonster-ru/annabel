<?php

namespace Codemonster\Cms\Modules\Auth\Services;

use Codemonster\Cms\Modules\Core\ModuleManager;

final class PermissionRegistry
{
    /** @var list<array{code: string, name: string, category: string, sort_order: int}>|null */
    private ?array $permissions = null;

    public function __construct(private ModuleManager $modules)
    {
    }

    /** @return list<array{code: string, name: string, category: string, sort_order: int}> */
    public function all(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }

        $permissions = [];

        foreach ($this->modules->definitions() as $module) {
            $declared = $module->metadata['permissions'] ?? [];

            if (!is_array($declared)) {
                throw new \RuntimeException("Module permissions must be an array: {$module->name}");
            }

            foreach ($declared as $permission) {
                $permission = $this->parse($permission, $module->name);

                if (isset($permissions[$permission['code']])) {
                    throw new \RuntimeException("Duplicate permission code: {$permission['code']}");
                }

                $permissions[$permission['code']] = $permission;
            }
        }

        uasort($permissions, static fn (array $left, array $right): int => [
            $left['sort_order'],
            $left['code'],
        ] <=> [
            $right['sort_order'],
            $right['code'],
        ]);

        return $this->permissions = array_values($permissions);
    }

    public function has(string $code): bool
    {
        foreach ($this->all() as $permission) {
            if ($permission['code'] === $code) {
                return true;
            }
        }

        return false;
    }

    /** @return array{code: string, name: string, category: string, sort_order: int} */
    private function parse(mixed $permission, string $module): array
    {
        if (!is_array($permission)) {
            throw new \RuntimeException("Permission declaration must be an array: {$module}");
        }

        $code = $permission['code'] ?? null;
        $name = $permission['name'] ?? null;
        $category = $permission['category'] ?? null;
        $sortOrder = $permission['sort_order'] ?? 0;

        if (!is_string($code) || preg_match('/\A[a-z][a-z0-9_.-]*\z/', $code) !== 1) {
            throw new \RuntimeException("Invalid permission code in module: {$module}");
        }
        if (!is_string($name) || $name === '' || !is_string($category) || $category === '') {
            throw new \RuntimeException("Permission requires name and category: {$module}");
        }
        if (!is_int($sortOrder)) {
            throw new \RuntimeException("Permission sort order must be an integer: {$module}");
        }

        return ['code' => $code, 'name' => $name, 'category' => $category, 'sort_order' => $sortOrder];
    }
}
