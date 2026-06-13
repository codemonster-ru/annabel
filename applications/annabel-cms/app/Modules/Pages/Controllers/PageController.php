<?php

namespace Codemonster\Xen\Modules\Pages\Controllers;

class PageController
{
    public function index()
    {
        return view('pages::home', ['title' => 'Home Page']);
    }
}
