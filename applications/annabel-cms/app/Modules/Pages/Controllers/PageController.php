<?php

namespace Codemonster\Cms\Modules\Pages\Controllers;

use Codemonster\Http\Response;
use Codemonster\View\View;

class PageController
{
    public function __construct(
        private View $view,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->view->render('pages::home', ['title' => 'Home Page']));
    }
}
