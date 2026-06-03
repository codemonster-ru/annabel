<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelQueryTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn() => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
        ];
    }

    public function test_get_returns_model_collection()
    {
        $users = User::all();

        $this->assertInstanceOf(ModelCollection::class, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertCount(2, $users);
    }

    public function test_first_applies_where()
    {
        $user = User::query()
            ->where('id', 2)
            ->first();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(2, $user->id);
        $this->assertEquals('Second', $user->name);
    }

    public function test_count_and_exists()
    {
        $this->assertTrue(User::query()->exists());
        $this->assertEquals(2, User::query()->count());

        $this->assertFalse(
            User::query()->where('name', 'Missing')->exists()
        );
    }

    public function test_value_and_pluck_return_results()
    {
        $names = User::query()->pluck('name');
        $first = User::query()->value('name');

        $this->assertSame(['First', 'Second'], $names);
        $this->assertSame('First', $first);
    }

    public function test_simple_paginate_returns_array()
    {
        $result = User::query()->simplePaginate(1, 1);

        $this->assertIsArray($result);
        $this->assertSame(1, $result['per_page']);
        $this->assertSame(1, $result['current_page']);
        $this->assertSame(2, $result['next_page']);
        $this->assertNull($result['prev_page']);
        $this->assertCount(1, $result['data']);
    }
}
