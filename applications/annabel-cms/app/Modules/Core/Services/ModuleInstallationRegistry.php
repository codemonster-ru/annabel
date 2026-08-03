<?php

namespace Codemonster\Cms\Modules\Core\Services;

use Codemonster\Database\Contracts\ConnectionInterface;

class ModuleInstallationRegistry
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    /**
     * @return array<string, true>
     */
    public function installedNames(): array
    {
        $installed = [];

        foreach ($this->states() ?? [] as $name => $state) {
            $installed[$name] = true;
        }

        return $installed;
    }

    /**
     * @return array<string, bool>|null Null means the installation registry is not available yet.
     */
    public function states(): ?array
    {
        try {
            $rows = $this->connection->table('module_installations')->get();
        } catch (\Throwable) {
            return null;
        }

        $states = [];

        foreach ($rows as $row) {
            $name = $row['name'] ?? null;

            if (is_string($name) && $name !== '') {
                $states[$name] = !array_key_exists('is_enabled', $row) || (bool) $row['is_enabled'];
            }
        }

        return $states;
    }

    public function enable(string $name): void
    {
        $this->connection->table('module_installations')
            ->where('name', $name)
            ->update(['is_enabled' => 1]);
    }

    public function disable(string $name): void
    {
        $this->connection->table('module_installations')
            ->where('name', $name)
            ->update(['is_enabled' => 0]);
    }

    public function install(string $name): void
    {
        $this->connection->table('module_installations')->insert([
            'name' => $name,
            'installed_at' => gmdate('Y-m-d H:i:s'),
            'is_enabled' => 0,
        ]);
    }

    public function uninstall(string $name): void
    {
        $this->connection->table('module_installations')
            ->where('name', $name)
            ->delete();
    }
}
