<?php

namespace Codemonster\Database\Tests\ORM\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use Codemonster\Database\Tests\Fakes\FakeModels\Profile;
use PHPUnit\Framework\TestCase;

class HasOneTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();
        Model::setConnectionResolver(fn() => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'WithProfile'],
        ];

        $this->conn->tables['profiles'] = [
            ['id' => 1, 'user_id' => 1, 'bio' => 'Tester'],
        ];
    }

    public function test_has_one_returns_related_model()
    {
        $user = User::find(1);

        $profile = $user->profile;

        $this->assertInstanceOf(Profile::class, $profile);
        $this->assertEquals('Tester', $profile->bio);
    }
}
