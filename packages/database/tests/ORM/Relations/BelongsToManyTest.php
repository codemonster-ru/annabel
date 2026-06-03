<?php

namespace Codemonster\Database\Tests\ORM\Relations;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\Role;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class BelongsToManyTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();
        Model::setConnectionResolver(fn() => $this->conn);

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Member'],
        ];

        $this->conn->tables['roles'] = [
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Editor'],
        ];

        $this->conn->tables['role_user'] = [
            ['user_id' => 1, 'role_id' => 2],
        ];
    }

    public function test_belongs_to_many_returns_collection()
    {
        $user = User::find(1);

        $roles = $user->roles;

        $this->assertCount(1, $roles);
        $this->assertInstanceOf(Role::class, $roles[0]);
        $this->assertEquals('Editor', $roles[0]->name);
    }
}
