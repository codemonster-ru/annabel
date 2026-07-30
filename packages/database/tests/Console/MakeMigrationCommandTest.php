<?php

namespace Codemonster\Database\Tests\Console;

use Codemonster\Database\Console\Commands\MakeMigrationCommand;
use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Tests\TestCase;
use Codemonster\DateTime\FrozenClock;

class MakeMigrationCommandTest extends TestCase
{
    public function test_make_migration_command_uses_clock_for_filename(): void
    {
        $dir = sys_get_temp_dir() . '/cm_db_migrations_' . uniqid('', true);
        mkdir($dir);

        $paths = new MigrationPathResolver();
        $paths->addPath($dir);
        $command = new MakeMigrationCommand(
            $paths,
            new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:45 UTC')),
        );

        $this->expectOutputRegex('/^Created migration: .+\\.php\\R$/');

        $result = $command->handle(['CreateUsersTable']);
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $this->assertSame(0, $result);
        $this->assertSame(
            [$dir . DIRECTORY_SEPARATOR . '2026_07_31_123045_create_users_table.php'],
            $files,
        );

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($dir);
    }
}
