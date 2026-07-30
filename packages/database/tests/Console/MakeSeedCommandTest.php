<?php

namespace Codemonster\Database\Tests\Console;

use Codemonster\Database\Console\Commands\MakeSeedCommand;
use Codemonster\Database\Seeders\SeedPathResolver;
use Codemonster\Database\Tests\TestCase;
use Codemonster\DateTime\FrozenClock;

class MakeSeedCommandTest extends TestCase
{
    public function test_make_seed_command_creates_file(): void
    {
        $dir = sys_get_temp_dir() . '/cm_db_seeds_' . uniqid('', true);

        mkdir($dir);

        $paths = new SeedPathResolver();
        $paths->addPath($dir);

        $command = new MakeSeedCommand(
            $paths,
            new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:45 UTC')),
        );

        $this->expectOutputRegex('/^Created seed: .+\\.php\\R$/');

        $result = $command->handle(['UsersSeeder']);

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $this->assertSame(0, $result);
        $this->assertCount(1, $files);
        $this->assertSame(
            $dir . DIRECTORY_SEPARATOR . '2026_07_31_123045_users_seeder.php',
            $files[0],
        );

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($dir);
    }

    public function test_make_seed_command_rejects_invalid_name(): void
    {
        $dir = sys_get_temp_dir() . '/cm_db_seeds_' . uniqid('', true);

        mkdir($dir);

        $paths = new SeedPathResolver();
        $paths->addPath($dir);

        $command = new MakeSeedCommand($paths);

        $this->expectOutputString("Seed name must be CamelCase, Latin letters only. Example: UsersSeeder\n");

        $result = $command->handle(['users_seeder']);

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $this->assertSame(1, $result);
        $this->assertCount(0, $files);

        rmdir($dir);
    }
}
