<?php

namespace Codemonster\Database\Tests\ORM;

use Codemonster\Database\ORM\Model;
use Codemonster\Database\Tests\Fakes\FakeConnection;
use Codemonster\Database\Tests\Fakes\FakeModels\User;
use Codemonster\Database\Traits\SoftDeletes;
use PHPUnit\Framework\TestCase;

class SoftDeletesTest extends TestCase
{
    protected FakeConnection $conn;

    protected function setUp(): void
    {
        $this->conn = new FakeConnection();

        Model::setConnectionResolver(fn () => $this->conn);
        SoftDeletingUser::flushModelEvents();

        $this->conn->tables['users'] = [
            ['id' => 1, 'name' => 'Active', 'deleted_at' => null],
            ['id' => 2, 'name' => 'Deleted', 'deleted_at' => '2024-01-01 10:00:00'],
        ];
    }

    public function test_soft_delete_marks_row()
    {
        $user = SoftDeletingUser::find(1);

        $this->assertFalse($user->trashed());

        $user->delete();

        $this->assertTrue($user->trashed());
        $this->assertNotNull($this->conn->tables['users'][0]['deleted_at']);
    }

    public function test_restore_clears_deleted_flag()
    {
        $user = SoftDeletingUser::find(2);

        $this->assertTrue($user->trashed());

        $user->restore();

        $this->assertFalse($user->trashed());
        $this->assertNull($this->conn->tables['users'][1]['deleted_at']);
    }

    public function test_query_scopes()
    {
        $without = SoftDeletingUser::withoutTrashed()->get();
        $only = SoftDeletingUser::onlyTrashed()->get();
        $with = SoftDeletingUser::withTrashed()->get();

        $this->assertCount(1, $without);
        $this->assertEquals('Active', $without[0]->name);

        $this->assertCount(1, $only);
        $this->assertEquals('Deleted', $only[0]->name);

        $this->assertCount(2, $with);
    }

    public function test_delete_returns_false_for_unsaved_model()
    {
        $user = new SoftDeletingUser(['name' => 'New']);

        $this->assertFalse($user->delete());
        $this->assertFalse($user->trashed());
    }

    public function test_soft_delete_and_restore_events_are_fired()
    {
        $events = [];

        foreach (['deleting', 'deleted', 'restoring', 'restored'] as $event) {
            SoftDeletingUser::on($event, function () use (&$events, $event) {
                $events[] = $event;
            });
        }

        SoftDeletingUser::find(1)->delete();
        SoftDeletingUser::find(2)->restore();

        $this->assertSame(['deleting', 'deleted', 'restoring', 'restored'], $events);
    }

    public function test_restore_event_can_cancel_restore()
    {
        SoftDeletingUser::on('restoring', fn () => false);

        $user = SoftDeletingUser::find(2);

        $this->assertFalse($user->restore());
        $this->assertTrue($user->trashed());
        $this->assertSame('2024-01-01 10:00:00', $this->conn->tables['users'][1]['deleted_at']);
    }
}

class SoftDeletingUser extends User
{
    use SoftDeletes;
}
