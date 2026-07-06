<?php

namespace Codemonster\Database\Tests\Seeders;

use Codemonster\Database\Seeders\SeedPathResolver;
use Codemonster\Database\Tests\TestCase;

class SeedPathResolverTest extends TestCase
{
    public function test_adds_nonexistent_path_and_keeps_unique(): void
    {
        $resolver = new SeedPathResolver();

        $path = sys_get_temp_dir() . '/cm_db_seeds_missing_' . uniqid('', true);

        $resolver->addPath($path);
        // Duplicate with trailing slash to ensure it stays unique
        $resolver->addPath($path . DIRECTORY_SEPARATOR);

        $paths = $resolver->getPaths();

        $this->assertSame([$path], $paths);
        $this->assertFalse(is_dir($path));
    }
}
