<?php

namespace Codemonster\Annabel\Tests\Providers;

use Codemonster\Annabel\Application;
use Codemonster\Annabel\Providers\CoreServiceProvider;
use Codemonster\Annabel\Providers\MailServiceProvider;
use Codemonster\Annabel\Publishing\PublishRegistry;
use Codemonster\Config\Config;
use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\MailManager;
use Codemonster\Mail\Message;
use PHPUnit\Framework\TestCase;

class MailServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Application::resetInstance();
        Config::reset();
    }

    public function test_mail_services_are_registered(): void
    {
        $app = $this->app([
            'mail.default' => 'array',
            'mail.mailers.array.transport' => 'array',
        ]);

        $sent = $app->make(MailerInterface::class)->send(
            Message::make()
                ->from('hello@example.com')
                ->to('user@example.com')
                ->subject('Welcome')
                ->text('Welcome.'),
        );

        self::assertInstanceOf(MailManager::class, $app->make(MailManager::class));
        self::assertInstanceOf(MailerInterface::class, $app->make('mailer'));
        self::assertSame('array', $sent->mailer());
    }

    public function test_mail_config_is_publishable(): void
    {
        $app = $this->app([]);

        /** @var PublishRegistry $registry */
        $registry = $app->make(PublishRegistry::class);
        $resources = $registry->matching(MailServiceProvider::class, 'mail');

        self::assertCount(1, $resources);
        self::assertSame($app->getBasePath() . '/config/mail.php', $resources[0]['destination']);
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

        (new MailServiceProvider($app))->register();

        return $app;
    }
}
