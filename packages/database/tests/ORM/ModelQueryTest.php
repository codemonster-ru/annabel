<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\ORM\ModelCollection;
use Codemonster\Database\ORM\ModelQuery;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelQueryTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn () => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second'],
            ['id' => 3, 'name' => 'Third'],
        ];

        $this->conn->tables['posts'] = [
            ['id' => 10, 'user_id' => 1, 'title' => 'First post'],
            ['id' => 11, 'user_id' => 1, 'title' => 'Second post'],
            ['id' => 12, 'user_id' => 2, 'title' => 'Third post'],
        ];
    }

    public function test_get_returns_model_collection()
    {
        $users = User::all();

        $this->assertInstanceOf(ModelCollection::class, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertCount(3, $users);
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
        $this->assertEquals(3, User::query()->count());

        $this->assertFalse(
            User::query()->where('name', 'Missing')->exists(),
        );
    }

    public function test_value_and_pluck_return_results()
    {
        $names = User::query()->pluck('name');
        $first = User::query()->value('name');

        $this->assertSame(['First', 'Second', 'Third'], $names);
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

    public function test_local_scopes_can_be_called_from_model_query()
    {
        $user = ScopedUser::query()
            ->named('Second')
            ->first();

        $this->assertInstanceOf(ScopedUser::class, $user);
        $this->assertSame('Second', $user->name);
    }

    public function test_with_eager_loads_relations()
    {
        $users = User::query()
            ->with('posts')
            ->get();

        $this->assertCount(2, $users[0]->posts);
        $this->assertSame('First post', $users[0]->posts[0]->title);
        $this->assertCount(1, $users[1]->posts);
        $this->assertSame(['users', 'posts'], $this->conn->tableReads);
    }

    public function test_with_eager_loads_nested_relations()
    {
        $user = User::query()
            ->with('posts.author')
            ->where('id', 1)
            ->first();

        $this->assertSame('First', $user->posts[0]->author->name);
        $this->assertSame(['users', 'posts', 'users'], $this->conn->tableReads);
    }

    public function test_paginate_returns_model_collection_and_meta()
    {
        $result = User::query()
            ->with('posts')
            ->paginate(2, 1);

        $this->assertInstanceOf(ModelCollection::class, $result['data']);
        $this->assertCount(2, $result['data']);
        $this->assertCount(2, $result['data'][0]->posts);
        $this->assertSame(3, $result['total']);
        $this->assertSame(2, $result['per_page']);
        $this->assertSame(1, $result['current_page']);
        $this->assertSame(2, $result['last_page']);
        $this->assertSame(1, $result['from']);
        $this->assertSame(2, $result['to']);
        $this->assertSame(2, $result['next_page']);
        $this->assertNull($result['prev_page']);
    }
}

class ScopedUser extends User
{
    public function scopeNamed(ModelQuery $query, string $name): ModelQuery
    {
        return $query->where('name', $name);
    }
}
