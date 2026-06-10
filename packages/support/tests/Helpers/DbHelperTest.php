<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\DatabaseManager;
use PHPUnit\Framework\TestCase;

class DbHelperTest extends TestCase
{
    public function test_db_returns_connection()
    {
        $manager = new DatabaseManager([
            'default' => 'fake',
            'connections' => [
                'fake' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ],
            ],
        ]);

        app()->instance(DatabaseManager::class, $manager);

        $this->assertInstanceOf(
            ConnectionInterface::class,
            db(),
        );
    }
}
