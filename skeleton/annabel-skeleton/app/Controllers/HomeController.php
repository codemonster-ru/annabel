<?php

namespace App\Controllers;

use Codemonster\Http\Response;

class HomeController
{
    public function index(): Response
    {
        return view('home', ['name' => 'World!']);
    }
}
