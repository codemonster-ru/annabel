<?php

namespace Codemonster\Cms\Modules\AdminUsers\Controllers;

use Codemonster\Cms\Modules\Admin\Contracts\AdminScreenRendererInterface;
use Codemonster\Http\Response;

class UserListController
{
    public function __construct(
        private AdminScreenRendererInterface $admin,
    ) {
    }

    public function index(): Response
    {
        return $this->admin->renderAuthenticated('admin.users.list');
    }
}
