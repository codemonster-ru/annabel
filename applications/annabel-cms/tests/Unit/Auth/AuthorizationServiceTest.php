<?php

namespace Codemonster\Cms\Tests\Unit\Auth;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Services\AuthorizationService;
use PHPUnit\Framework\TestCase;

class AuthorizationServiceTest extends TestCase
{
    public function testItChecksAbilitiesAndAppliesTheAdministratorBypass(): void
    {
        $authorization = new AuthorizationService();
        $editor = new AuthenticatedUser(1, 'editor', 'editor@example.com', ['editor'], ['pages.update']);
        $admin = new AuthenticatedUser(2, 'admin', 'admin@example.com', ['admin']);

        self::assertTrue($authorization->allows($editor, 'pages.update'));
        self::assertTrue($authorization->denies($editor, 'pages.delete'));
        self::assertTrue($authorization->allows($admin, 'any.registered.ability'));
        self::assertFalse($authorization->allows($admin, ''));
    }
}
