<?php

use Annabel\Application;

if (!function_exists('app')) {
    function app(): Application
    {
        return Application::getInstance();
    }
}
