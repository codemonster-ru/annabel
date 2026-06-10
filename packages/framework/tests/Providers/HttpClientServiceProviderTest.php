<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\HttpClientServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use Codemonster\HttpClient\HttpClient;
use PHPUnit\Framework\TestCase;

class HttpClientServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_http_client_service_is_registered(): void
    {
        $app = $this->app([
            'http-client.base_url' => 'https://api.example.com',
            'http-client.timeout' => 10,
            'http-client.headers.Accept' => 'application/json',
        ]);

        self::assertInstanceOf(HttpClient::class, $app->make(HttpClient::class));
        self::assertInstanceOf(HttpClient::class, $app->make('http.client'));
    }

    public function test_http_client_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(HttpClientServiceProvider::class, 'http-client');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/http-client.php', $resources[0]['destination']);
        self::assertFileExists($resources[0]['source']);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function app(array $configuration): Application
    {
        Application::resetInstance();

        $app = new Application(__DIR__ . '/../..', null, false);
        (new CoreServiceProvider($app))->register();

        config($configuration);

        (new HttpClientServiceProvider($app))->register();

        return $app;
    }
}
