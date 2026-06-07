<?php

namespace Codemonster\Database\Tests\Console;

use Codemonster\Database\Console\Commands\MakeSeedCommand;
use Codemonster\Database\Seeders\SeedPathResolver;
use Codemonster\Database\Tests\TestCase;

class MakeSeedCommandTest extends TestCase
{
    public function test_make_seed_command_creates_file()
    {
        $dir = sys_get_temp_dir() . '/cm_db_seeds_' . uniqid('', true);

        mkdir($dir);

        $paths = new SeedPathResolver();
        $paths->addPath($dir);

        $command = new MakeSeedCommand($paths);

        $this->expectOutputRegex('/^Created seed: .+\\.php\\R$/');

        $result = $command->handle(['UsersSeeder']);

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [];

        $this->assertSame(0, $result);
        $this->assertCount(1, $files);

        foreach ($files as $file) {
            unlink($file);
        }

        rmdir($dir);
    }

    public function test_make_seed_command_rejects_invalid_name()
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
