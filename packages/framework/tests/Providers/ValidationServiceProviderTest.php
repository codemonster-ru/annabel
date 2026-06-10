<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\ValidationServiceProvider;
use Codemonster\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidationServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
    }

    public function test_validation_services_are_registered(): void
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new ValidationServiceProvider($app))->register();

        self::assertInstanceOf(Validator::class, $app->make(Validator::class));
        self::assertInstanceOf(Validator::class, $app->make('validator'));
    }
}
