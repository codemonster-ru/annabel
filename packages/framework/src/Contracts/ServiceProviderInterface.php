<?php

namespace Codemonster\Annabel\Contracts;

use Codemonster\Annabel\Application;

interface ServiceProviderInterface
{
    public function __construct(Application $app);
    public function register(): void;
    public function boot(): void;
}
