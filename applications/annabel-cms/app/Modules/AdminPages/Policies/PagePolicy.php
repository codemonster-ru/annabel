<?php

namespace Codemonster\Cms\Modules\AdminPages\Policies;

use Codemonster\Cms\Modules\Auth\Contracts\AuthenticatedUser;
use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationPolicyInterface;
use Codemonster\Cms\Modules\Auth\Models\User;
use Codemonster\Cms\Modules\Pages\Models\Page;

final class PagePolicy implements AuthorizationPolicyInterface
{
    public function allows(AuthenticatedUser|User $user, string $ability, object $subject): bool
    {
        if (!$subject instanceof Page) {
            return false;
        }

        return match ($ability) {
            'pages.update' => $this->globalOrOwned($user, $subject, 'pages.update', 'pages.update.own'),
            'pages.delete' => $this->globalOrOwned($user, $subject, 'pages.delete', 'pages.delete.own'),
            'pages.publish' => $this->globalOrOwned($user, $subject, 'pages.publish', 'pages.publish.own'),
            'pages.assign_owner' => $user->hasPermission('pages.assign_owner'),
            default => $user->hasPermission($ability),
        };
    }

    private function globalOrOwned(AuthenticatedUser|User $user, Page $page, string $global, string $owned): bool
    {
        return $user->hasPermission($global)
            || ($user->hasPermission($owned) && $page->isOwnedBy($user->id));
    }
}
