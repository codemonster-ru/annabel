<?php

namespace Codemonster\Cms\Modules\Admin\Controllers;

use Codemonster\Cms\Modules\Admin\Services\AdminShellRenderer;
use Codemonster\Http\Response;

class AdminShellController
{
    public function __construct(
        private AdminShellRenderer $renderer,
    ) {
    }

    public function index(): Response
    {
        return $this->renderer->render(true, 'dashboard');
    }
}
