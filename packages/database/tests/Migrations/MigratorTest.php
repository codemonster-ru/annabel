<?php

namespace Codemonster\Database\Tests\Migrations;

use Codemonster\Database\Migrations\MigrationPathResolver;
use Codemonster\Database\Migrations\MigrationRepository;
use Codemonster\Database\Migrations\Migrator;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\TestCase;

class MigratorTest extends TestCase
{
    public function test_migrator_runs_migrations_in_transaction()
    {
        $dir = sys_get_temp_dir() . '/cm_db_migrations_' . uniqid('', true);

        mkdir($dir);

        $name = '2025_01_01_000000_test_migration';
        $file = $dir . DIRECTORY_SEPARATOR . $name . '.php';

        file_put_contents($file, <<<PHP
        <?php

        use Codemonster\\Database\\Migrations\\Migration;

        return new class extends Migration {
            public function up(): void {}
            public function down(): void {}
        };
        PHP);

        $conn = new FakeConnection();
        $repo = new MigrationRepository($conn);

        /** @var MigrationPathResolver $paths */
        $paths = $this->createStub(MigrationPathResolver::class);
        $paths->method('getPaths')->willReturn([$dir]);

        $migrator = new Migrator($repo, $conn, $paths);

        $ran = $migrator->migrate();

        $this->assertCount(1, $ran);
        $this->assertSame($name, $ran[0]);
        $this->assertTrue($conn->transactionStarted);
        $this->assertTrue($conn->transactionCommitted);
        $this->assertFalse($conn->transactionRolledBack);

        $logged = $repo->getRan();

        $this->assertCount(1, $logged);
        $this->assertSame($name, $logged[0]['migration']);

        unlink($file);
        rmdir($dir);
    }

    public function test_migrator_skips_already_ran_migrations()
    {
        $dir = sys_get_temp_dir() . '/cm_db_migrations_' . uniqid('', true);

        mkdir($dir);

        $first = '2025_01_01_000000_first_migration';
        $second = '2025_01_02_000000_second_migration';

        foreach ([$first, $second] as $name) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.php', <<<PHP
            <?php

            use Codemonster\\Database\\Migrations\\Migration;

            return new class extends Migration {
                public function up(): void {}
                public function down(): void {}
            };
            PHP);
        }

        $conn = new FakeConnection();
        $conn->migrations = [$first];

        $repo = new MigrationRepository($conn);

        /** @var MigrationPathResolver $paths */
        $paths = $this->createStub(MigrationPathResolver::class);
        $paths->method('getPaths')->willReturn([$dir]);

        $migrator = new Migrator($repo, $conn, $paths);

        $ran = $migrator->migrate();

        $this->assertSame([$second], $ran);
        $this->assertSame([$first, $second], $conn->migrations);

        foreach ([$first, $second] as $name) {
            unlink($dir . DIRECTORY_SEPARATOR . $name . '.php');
        }

        rmdir($dir);
    }
}
