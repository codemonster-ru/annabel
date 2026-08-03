<?php

namespace Codemonster\Cms\Tests\Unit\Auth;

use Codemonster\Cms\Modules\Auth\Models\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    /** @param array<string, mixed> $attributes */
    #[DataProvider('activityWindows')]
    public function testItDeterminesWhetherGroupIsActive(array $attributes, bool $expected): void
    {
        $group = new Group($attributes);

        self::assertSame($expected, $group->isActiveAt(new \DateTimeImmutable('2026-08-03 12:00:00 UTC')));
    }

    /** @return iterable<string, array{array<string, mixed>, bool}> */
    public static function activityWindows(): iterable
    {
        yield 'disabled without a window' => [['is_active' => false], false];
        yield 'enabled without a window' => [['is_active' => true], true];
        yield 'at start boundary' => [['is_active' => true, 'active_from' => '2026-08-03 12:00:00'], true];
        yield 'before start' => [['is_active' => true, 'active_from' => '2026-08-03 12:00:01'], false];
        yield 'at end boundary' => [['is_active' => true, 'active_until' => '2026-08-03 12:00:00'], true];
        yield 'after end' => [['is_active' => true, 'active_until' => '2026-08-03 11:59:59'], false];
        yield 'inside window' => [[
            'is_active' => true,
            'active_from' => '2026-08-03 11:00:00',
            'active_until' => '2026-08-03 13:00:00',
        ], true];
        yield 'window does not override disabled flag' => [[
            'is_active' => false,
            'active_from' => '2026-08-03 11:00:00',
            'active_until' => '2026-08-03 13:00:00',
        ], false];
    }
}
