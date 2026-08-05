<?php

namespace Codemonster\Cms\Modules\AdminPages;

use Codemonster\Annabel\Providers\ServiceProvider;
use Codemonster\Cms\Modules\AdminPages\Policies\PagePolicy;
use Codemonster\Cms\Modules\Auth\Contracts\AuthorizationInterface;
use Codemonster\Cms\Modules\Auth\Services\AuthorizationService;
use Codemonster\Cms\Modules\Pages\Models\Page;

final class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $authorization = $this->app()->make(AuthorizationInterface::class);
        if (!$authorization instanceof AuthorizationService) {
            throw new \RuntimeException('Authorization service does not support policy registration.');
        }

        $authorization->registerPolicy(Page::class, PagePolicy::class);
    }
}
