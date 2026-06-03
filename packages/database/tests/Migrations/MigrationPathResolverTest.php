<?php

namespace Codemonster\Database\Tests\Migrations;

use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Tests\TestCase;

class MigrationPathResolverTest extends TestCase
{
    public function test_adds_nonexistent_path_and_keeps_unique()
    {
        $resolver = new MigrationPathResolver();

        $path = sys_get_temp_dir() . '/cm_db_missing_' . uniqid('', true);

        $resolver->addPath($path);
        // Duplicate with trailing slash to ensure it stays unique
        $resolver->addPath($path . DIRECTORY_SEPARATOR);

        $paths = $resolver->getPaths();

        $this->assertSame([$path], $paths);
        $this->assertFalse(is_dir($path));
    }
}

