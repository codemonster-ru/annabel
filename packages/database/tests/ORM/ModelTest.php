<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn () => $this->conn);
        User::flushModelEvents();

        // seed some rows
        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Vasya', 'email' => 'v@example.com'],
        ];
    }

    public function test_find_returns_model(): void
    {
        $user = User::find(1);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Vasya', $user->name);
    }

    public function test_create_inserts_row(): void
    {
        $user = User::create([
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('Test', $user->name);
        $this->assertEquals('test@example.com', $this->conn->tables['users'][1]['email']);
    }

    public function test_save_updates_existing(): void
    {
        $user = User::find(1);
        $this->assertInstanceOf(User::class, $user);
        $user->name = 'Updated';
        $user->save();

        $this->assertEquals('Updated', $this->conn->tables['users'][0]['name']);
    }

    public function test_casts_are_applied(): void
    {
        $model = new CastingUser([
            'id' => '5',
            'active' => 'false',
            'rating' => '3.5',
            'meta' => '{"a":1}',
            'data' => '{"a":1}',
            'settings' => '{"enabled":true}',
            'price' => '12.345',
            'joined_at' => '2024-01-01 12:00:00',
            'birth_date' => '2024-01-01',
        ]);

        $this->assertSame(5, $model->id);
        $this->assertFalse($model->active);
        $this->assertSame(3.5, $model->rating);
        $this->assertSame(['a' => 1], $model->meta);
        $this->assertSame(['a' => 1], $model->data);
        $this->assertIsObject($model->settings);
        /** @var object{enabled: bool} $settings */
        $settings = $model->settings;
        $this->assertTrue($settings->enabled);
        $this->assertSame('12.35', $model->price);
        $this->assertInstanceOf(\DateTimeImmutable::class, $model->joined_at);
        $this->assertInstanceOf(\DateTimeImmutable::class, $model->birth_date);
        $this->assertSame('00:00:00', $model->birth_date->format('H:i:s'));
    }

    public function test_accessors_and_mutators_are_applied(): void
    {
        $model = new AccessorUser(['name' => '  vasya  ']);

        $this->assertSame('VASYA', $model->name);
        $this->assertSame(['name' => 'VASYA'], $model->getAttributes());
    }

    public function test_guarded_and_fillable_rules(): void
    {
        $guarded = new GuardedUser(['name' => 'Nope']);
        $fillable = new FillableUser(['name' => 'Ok', 'email' => 'skip@example.com']);

        $this->assertSame([], $guarded->getAttributes());
        $this->assertSame(['name' => 'Ok'], $fillable->getAttributes());
    }

    public function test_model_events_are_fired_for_create_and_update(): void
    {
        $events = [];

        foreach (['saving', 'creating', 'created', 'saved', 'updating', 'updated'] as $event) {
            User::on($event, function () use (&$events, $event) {
                $events[] = $event;
            });
        }

        $user = User::create([
            'name' => 'Created',
            'email' => 'created@example.com',
        ]);

        $user->name = 'Updated';
        $user->save();

        $this->assertSame(
            ['saving', 'creating', 'created', 'saved', 'saving', 'updating', 'updated', 'saved'],
            $events,
        );
    }

    public function test_model_event_can_cancel_save(): void
    {
        User::on('creating', fn () => false);

        $user = new User([
            'name' => 'Cancelled',
            'email' => 'cancelled@example.com',
        ]);

        $this->assertFalse($user->save());
        $this->assertCount(1, $this->conn->tables['users']);
    }

    public function test_observer_methods_are_called(): void
    {
        $observer = new class () {
            /** @var list<string> */
            public array $events = [];

            public function creating(User $user): void
            {
                $this->events[] = 'creating:' . $user->name;
            }

            public function updated(User $user): void
            {
                $this->events[] = 'updated:' . $user->name;
            }

            public function deleted(User $user): void
            {
                $this->events[] = 'deleted:' . $user->name;
            }
        };

        User::observe($observer);

        $created = User::create([
            'name' => 'Observed',
            'email' => 'observed@example.com',
        ]);

        $created->name = 'Observed Updated';
        $created->save();
        $created->delete();

        $this->assertSame(
            ['creating:Observed', 'updated:Observed Updated', 'deleted:Observed Updated'],
            $observer->events,
        );
    }
}

/**
 * @property int $id
 * @property bool $active
 * @property float $rating
 * @property array<string, mixed> $meta
 * @property array<string, mixed> $data
 * @property object{enabled: bool} $settings
 * @property string $price
 * @property \DateTimeImmutable $joined_at
 * @property \DateTimeImmutable $birth_date
 */
class CastingUser extends Model
{
    protected array $guarded = [];
    protected array $casts = [
        'id' => 'int',
        'active' => 'bool',
        'rating' => 'float',
        'meta' => 'array',
        'data' => 'json',
        'settings' => 'object',
        'price' => 'decimal:2',
        'joined_at' => 'datetime',
        'birth_date' => 'date',
    ];
}

/** @property string $name */
class AccessorUser extends Model
{
    protected array $guarded = [];

    public function getNameAttribute(mixed $value): string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable && $value !== null) {
            return '';
        }

        return strtoupper((string) $value);
    }

    public function setNameAttribute(mixed $value): string
    {
        if (!is_scalar($value) && !$value instanceof \Stringable && $value !== null) {
            return '';
        }

        return trim((string) $value);
    }
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
