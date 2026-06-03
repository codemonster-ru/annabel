<?php

namespace Codemonster\Database\Tests\ORM\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class HasManyTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();
        Model::setConnectionResolver(fn() => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Author'],
        ];

        $this->conn->tables['posts'] = [
            ['id' => 1, 'title' => 'First', 'user_id' => 1],
            ['id' => 2, 'title' => 'Second', 'user_id' => 1],
        ];
    }

    public function test_has_many_returns_collection()
    {
        $user = User::find(1);

        $posts = $user->posts;

        $this->assertCount(2, $posts);
        $this->assertEquals('First', $posts[0]->title);
        $this->assertEquals('Second', $posts[1]->title);
    }
}
