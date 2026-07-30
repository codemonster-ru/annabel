<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\QueueServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use Codemonster\DateTime\FrozenClock;
use Codemonster\Queue\Contracts\JobInterface;
use Codemonster\Queue\Contracts\QueueInterface;
use Codemonster\Queue\QueueManager;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

class QueueServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_queue_services_are_registered(): void
    {
        $app = $this->app([
            'queue.default' => 'sync',
            'queue.connections.sync.driver' => 'sync',
        ]);

        $job = new TestProviderJob();
        $app->make(QueueInterface::class)->push($job);

        self::assertInstanceOf(QueueManager::class, $app->make(QueueManager::class));
        self::assertInstanceOf(QueueInterface::class, $app->make('queue'));
        self::assertSame(1, $job->runs);
    }

    public function test_queue_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(QueueServiceProvider::class, 'queue');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/queue.php', $resources[0]['destination']);
        self::assertFileExists($resources[0]['source']);
    }

    public function test_queue_manager_uses_the_registered_clock(): void
    {
        $app = $this->app([
            'queue.default' => 'sync',
            'queue.connections.sync.driver' => 'sync',
        ]);
        $clock = new FrozenClock(new \DateTimeImmutable('2026-06-09 10:15:00 UTC'));
        $app->getContainer()->instance(ClockInterface::class, $clock);
        $manager = $app->make(QueueManager::class);

        $property = new \ReflectionProperty(QueueManager::class, 'clock');

        self::assertSame($clock, $property->getValue($manager));
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

        (new QueueServiceProvider($app))->register();

        return $app;
    }
}

class TestProviderJob implements JobInterface
{
    public int $runs = 0;

    public function handle(): void
    {
        $this->runs++;
    }
}
