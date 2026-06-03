<?php

namespace Codemonster\Database\Tests\ORM\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\Post;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class BelongsToTest extends TestCase
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
        ];
    }

    public function test_belongs_to_returns_parent_model()
    {
        $post = Post::find(1);

        $author = $post->author;

        $this->assertInstanceOf(User::class, $author);
        $this->assertEquals('Author', $author->name);
    }
}
