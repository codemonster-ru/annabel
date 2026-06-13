<?php

namespace Codemonster\Cms\Tests\Unit\Auth;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use PHPUnit\Framework\TestCase;

class AuthenticatedUserTest extends TestCase
{
    public function testItExposesStableIdentityPayload(): void
    {
        $user = new AuthenticatedUser(42, 'admin@example.com', ['admin', 'editor']);

        self::assertTrue($user->hasRole('admin'));
        self::assertFalse($user->hasRole('missing'));
        self::assertSame([
            'id' => 42,
            'email' => 'admin@example.com',
            'roles' => ['admin', 'editor'],
            'role' => 'admin',
        ], $user->toArray());
    }
}
