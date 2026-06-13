<?php

namespace Codemonster\Xen\Modules\Admin\Controllers;

use Codemonster\Xen\Modules\Core\ModuleManager;

class DashboardController
{
    protected ModuleManager $manager;

    public function __construct(ModuleManager $manager)
    {
        $this->manager = $manager;
    }

    public function index(): string
    {
        $modules = $this->manager->listAll();

        return view('admin::dashboard', [
            'modules' => $modules,
        ]);
    }
}
