<?php

namespace Codemonster\Cms\Tests\Unit\AdminPages;

use Codemonster\Cms\Modules\AdminPages\Policies\PagePolicy;
use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Pages\Models\Page;
use PHPUnit\Framework\TestCase;

class PagePolicyTest extends TestCase
{
    public function testOwnPermissionOnlyAppliesToOwnedPage(): void
    {
        $policy = new PagePolicy();
        $user = new AuthenticatedUser(
            42,
            'editor',
            'editor@example.com',
            ['editor'],
            ['pages.update.own', 'pages.delete.own'],
        );

        self::assertTrue($policy->allows($user, 'pages.update', new Page(['owner_id' => 42])));
        self::assertTrue($policy->allows($user, 'pages.delete', new Page(['owner_id' => '42'])));
        self::assertFalse($policy->allows($user, 'pages.update', new Page(['owner_id' => 7])));
        self::assertFalse($policy->allows($user, 'pages.delete', new Page(['owner_id' => null])));
    }

    public function testGlobalPermissionAppliesRegardlessOfOwner(): void
    {
        $policy = new PagePolicy();
        $user = new AuthenticatedUser(
            7,
            'chief-editor',
            'chief@example.com',
            ['chief-editor'],
            ['pages.update', 'pages.delete'],
        );
        $page = new Page(['owner_id' => 42]);

        self::assertTrue($policy->allows($user, 'pages.update', $page));
        self::assertTrue($policy->allows($user, 'pages.delete', $page));
    }

    public function testPublishingAndOwnershipTransferAreSeparateAbilities(): void
    {
        $policy = new PagePolicy();
        $publisher = new AuthenticatedUser(
            42,
            'publisher',
            'publisher@example.com',
            ['publisher'],
            ['pages.update.own', 'pages.publish.own'],
        );
        $ownerManager = new AuthenticatedUser(
            7,
            'owner-manager',
            'owner@example.com',
            ['owner-manager'],
            ['pages.assign_owner'],
        );
        $page = new Page(['owner_id' => 42]);

        self::assertTrue($policy->allows($publisher, 'pages.publish', $page));
        self::assertFalse($policy->allows($publisher, 'pages.assign_owner', $page));
        self::assertTrue($policy->allows($ownerManager, 'pages.assign_owner', $page));
        self::assertFalse($policy->allows($ownerManager, 'pages.update', $page));
    }
}
