<?php

use PHPUnit\Framework\TestCase;
use Codemonster\Database\Schema\Schema;
use Codemonster\Database\DatabaseManager;

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
            schema()
        );
    }
}
