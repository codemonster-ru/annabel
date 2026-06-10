<?php

namespace Codemonster\Database\Tests\Seeders;

use Codemonster\Database\Seeders\SeederRunner;
use Codemonster\Database\Seeders\SeedPathResolver;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\TestCase;

class SeederRunnerTest extends TestCase
{
    public function test_get_seed_files_returns_sorted_map()
    {
        $dir = sys_get_temp_dir() . '/cm_db_seeds_' . uniqid('', true);

        mkdir($dir);

        $first = '2025_01_01_000000_first_seed';
        $second = '2025_01_02_000000_second_seed';

        foreach ([$second, $first] as $name) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . $name . '.php', "<?php\n");
        }

        $paths = new SeedPathResolver();
        $paths->addPath($dir);

        $runner = new SeederRunner(new FakeConnection(), $paths);

        $files = $runner->getSeedFiles();

        $this->assertSame([$first, $second], array_keys($files));

        foreach ([$first, $second] as $name) {
            unlink($dir . DIRECTORY_SEPARATOR . $name . '.php');
        }

        rmdir($dir);
    }

    public function test_seeder_runner_runs_seeders_in_transaction()
    {
        $dir = sys_get_temp_dir() . '/cm_db_seeds_' . uniqid('', true);

        mkdir($dir);

        $first = '2025_01_01_000000_first_seed';
        $second = '2025_01_02_000000_second_seed';

        file_put_contents($dir . DIRECTORY_SEPARATOR . $first . '.php', <<<PHP
        <?php

        use Codemonster\\Database\\Seeders\\Seeder;

        return new class extends Seeder {
            public function run(): void
            {
                \$GLOBALS['seed_log'][] = 'first';
            }
        };
        PHP);

        file_put_contents($dir . DIRECTORY_SEPARATOR . $second . '.php', <<<PHP
        <?php

        use Codemonster\\Database\\Seeders\\Seeder;

        return new class extends Seeder {
            public function run(): void
            {
                \$GLOBALS['seed_log'][] = 'second';
            }
        };
        PHP);

        $GLOBALS['seed_log'] = [];

        $paths = new SeedPathResolver();
        $paths->addPath($dir);

        $conn = new FakeConnection();
        $runner = new SeederRunner($conn, $paths);

        $ran = $runner->seed();

        $this->assertSame([$first, $second], $ran);
        $this->assertSame(['first', 'second'], $GLOBALS['seed_log']);
        $this->assertTrue($conn->transactionStarted);
        $this->assertTrue($conn->transactionCommitted);
        $this->assertFalse($conn->transactionRolledBack);

        foreach ([$first, $second] as $name) {
            unlink($dir . DIRECTORY_SEPARATOR . $name . '.php');
        }

        rmdir($dir);
    }
}
