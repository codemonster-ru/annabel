<?php

namespace Codemonster\Cms\Tests\Unit\Core;

use Codemonster\Cms\Modules\Core\Services\ModuleInstallationRegistry;
use Codemonster\Database\Connection;
use PHPUnit\Framework\TestCase;

class ModuleInstallationRegistryTest extends TestCase
{
    public function testItPersistsInstallationVersionAndUpdatesIt(): void
    {
        $connection = new Connection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection->statement(<<<SQL
            CREATE TABLE module_installations (
                name TEXT PRIMARY KEY,
                installed_version TEXT NULL,
                installed_at TEXT NOT NULL,
                updated_at TEXT NULL,
                is_enabled INTEGER NOT NULL
            )
            SQL);
        $registry = new ModuleInstallationRegistry($connection);

        $registry->install('Demo', '1.0.0');

        $record = $registry->records()['Demo'] ?? null;
        self::assertNotNull($record);
        self::assertSame('1.0.0', $record['installed_version']);
        self::assertFalse($record['is_enabled']);
        self::assertNotNull($record['installed_at']);
        self::assertNotNull($record['updated_at']);

        $registry->markUpdated('Demo', '1.1.0');

        self::assertSame('1.1.0', $registry->records()['Demo']['installed_version'] ?? null);
    }
}
