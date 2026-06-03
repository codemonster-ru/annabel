<?php

namespace Codemonster\Database\Tests\ORM;

use PHPUnit\Framework\TestCase;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use Codemonster\Database\ORM\Model;

class ModelTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn() => $this->conn);

        // seed some rows
        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Vasya', 'email' => 'v@example.com'],
        ];
    }

    public function test_find_returns_model()
    {
        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Vasya', $user->name);
    }

    public function test_create_inserts_row()
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com'
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test', $user->name);
        $this->assertEquals('test@example.com', $this->conn->tables['users'][1]['email']);
    }

    public function test_save_updates_existing()
    {
        $user = User::find(1);
        $user->name = 'Updated';
        $user->save();

        $this->assertEquals('Updated', $this->conn->tables['users'][0]['name']);
    }

    public function test_casts_are_applied()
    {
        $model = new CastingUser([
            'id' => '5',
            'active' => '1',
            'rating' => '3.5',
            'meta' => ['a' => 1],
            'data' => '{"a":1}',
            'joined_at' => '2024-01-01 12:00:00',
            'birth_date' => '2024-01-01',
        ]);

        $this->assertSame(5, $model->id);
        $this->assertTrue($model->active);
        $this->assertSame(3.5, $model->rating);
        $this->assertSame(['a' => 1], $model->meta);
        $this->assertSame(['a' => 1], $model->data);
        $this->assertInstanceOf(\DateTimeImmutable::class, $model->joined_at);
        $this->assertInstanceOf(\DateTimeImmutable::class, $model->birth_date);
        $this->assertSame('00:00:00', $model->birth_date->format('H:i:s'));
    }

    public function test_guarded_and_fillable_rules()
    {
        $guarded = new GuardedUser(['name' => 'Nope']);
        $fillable = new FillableUser(['name' => 'Ok', 'email' => 'skip@example.com']);

        $this->assertSame([], $guarded->getAttributes());
        $this->assertSame(['name' => 'Ok'], $fillable->getAttributes());
    }
}

class CastingUser extends Model
{
    protected array $guarded = [];
    protected array $casts = [
        'id' => 'int',
        'active' => 'bool',
        'rating' => 'float',
        'meta' => 'array',
        'data' => 'json',
        'joined_at' => 'datetime',
        'birth_date' => 'date',
    ];
}

class GuardedUser extends Model
{
    protected array $guarded = ['*'];
}

class FillableUser extends Model
{
    protected array $guarded = ['*'];
    protected array $fillable = ['name'];
}
