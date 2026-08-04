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

        foreach ($this->records() ?? [] as $name => $record) {
            $installed[$name] = true;
        }

        return $installed;
    }

    /**
     * @return array<string, array{is_enabled: bool, installed_version: ?string, installed_at: ?string, updated_at: ?string}>|null
     *     Null means the installation registry is not available yet.
     */
    public function records(): ?array
    {
        try {
            $rows = $this->connection->table('module_installations')->get();
        } catch (\Throwable) {
            return null;
        }

        $records = [];

        foreach ($rows as $row) {
            $name = $row['name'] ?? null;

            if (!is_string($name) || $name === '') {
                continue;
            }

            $installedVersion = $row['installed_version'] ?? null;
            $installedAt = $row['installed_at'] ?? null;
            $updatedAt = $row['updated_at'] ?? null;

            $records[$name] = [
                'is_enabled' => !array_key_exists('is_enabled', $row) || (bool) $row['is_enabled'],
                'installed_version' => is_string($installedVersion) && $installedVersion !== ''
                    ? $installedVersion
                    : null,
                'installed_at' => is_string($installedAt) && $installedAt !== '' ? $installedAt : null,
                'updated_at' => is_string($updatedAt) && $updatedAt !== '' ? $updatedAt : null,
            ];
        }

        return $records;
    }

    /**
     * @return array<string, bool>|null Null means the installation registry is not available yet.
     */
    public function states(): ?array
    {
        $records = $this->records();

        if ($records === null) {
            return null;
        }

        $states = [];

        foreach ($records as $name => $record) {
            $states[$name] = $record['is_enabled'];
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

    public function install(string $name, string $version): void
    {
        $now = gmdate('Y-m-d H:i:s');

        $this->connection->table('module_installations')->insert([
            'name' => $name,
            'installed_version' => $version,
            'installed_at' => $now,
            'updated_at' => $now,
            'is_enabled' => 0,
        ]);
    }

    public function markUpdated(string $name, string $version): void
    {
        $this->connection->table('module_installations')
            ->where('name', $name)
            ->update([
                'installed_version' => $version,
                'updated_at' => gmdate('Y-m-d H:i:s'),
            ]);
    }

    public function uninstall(string $name): void
    {
        $this->connection->table('module_installations')
            ->where('name', $name)
            ->delete();
    }
}
