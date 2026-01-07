<?php

namespace Codemonster\Xen\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Contracts\ServiceProviderInterface;
use Codemonster\Database\Contracts\ConnectionInterface;
use Codemonster\Database\ORM\Model;

class OrmServiceProvider implements ServiceProviderInterface
{
    public function __construct(protected Application $app) {}

    public function register(): void
    {
        Model::setConnectionResolver(function (string $modelClass): ConnectionInterface {
            return $this->app->make('Codemonster\\Database\\Contracts\\ConnectionInterface');
        });
    }

    public function boot(): void {}
}
