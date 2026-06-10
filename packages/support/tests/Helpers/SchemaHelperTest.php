<?php

namespace Codemonster\Support\Tests\Helpers;

use Codemonster\Database\DatabaseManager;
use Codemonster\Database\Schema\Schema;
use PHPUnit\Framework\TestCase;

class SchemaHelperTest extends TestCase
{
    public function test_schema_returns_schema_builder()
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
            Schema::class,
            schema(),
        );
    }
}
