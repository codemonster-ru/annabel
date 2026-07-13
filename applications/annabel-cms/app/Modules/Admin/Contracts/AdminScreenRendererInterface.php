<?php

namespace Codemonster\Cms\Modules\Admin\Contracts;

use Codemonster\Http\Response;

interface AdminScreenRendererInterface
{
    public function renderAuthenticated(
        string $screen,
        ?string $navigationValue = null,
        ?string $pageTitle = null,
    ): Response;
}
