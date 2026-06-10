<?php

namespace Codemonster\Annabel\Providers;

use Codemonster\Annabel\Container;
use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\MailManager;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/mail.php' => $this->app()->getBasePath() . '/config/mail.php',
        ], ['config', 'mail']);

        $this->app()->singleton(MailManager::class, fn (Container $app): MailManager => new MailManager(
            $this->mailConfig(),
            $app->has(LoggerInterface::class) ? $app->make(LoggerInterface::class) : new NullLogger(),
        ));
        $this->app()->singleton('mail.manager', fn (Container $app): MailManager => $app->make(MailManager::class));
        $this->app()->singleton(MailerInterface::class, fn (Container $app): MailerInterface => $app
            ->make(MailManager::class)
            ->mailer());
        $this->app()->singleton('mailer', fn (Container $app): MailerInterface => $app->make(MailerInterface::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function mailConfig(): array
    {
        $config = config('mail', [
            'default' => 'log',
            'mailers' => [
                'log' => [
                    'transport' => 'log',
                ],
            ],
        ]);

        if (!is_array($config)) {
            throw new \RuntimeException('Mail config must be an array.');
        }

        $normalized = [];
        foreach ($config as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
