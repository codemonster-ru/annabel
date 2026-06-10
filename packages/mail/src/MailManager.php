<?php

namespace Codemonster\Mail;

use Codemonster\Mail\Contracts\MailerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MailManager
{
    /** @var array<string, mixed> */
    protected array $config;
    /** @var array<string, MailerInterface> */
    protected array $mailers = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config, protected ?LoggerInterface $logger = null)
    {
        $this->config = $config;
    }

    public function defaultMailer(): string
    {
        $default = $this->config['default'] ?? 'array';

        return is_string($default) && $default !== '' ? $default : 'array';
    }

    public function mailer(?string $name = null): MailerInterface
    {
        $name ??= $this->defaultMailer();

        if ($name === '') {
            throw new MailException('Mailer name cannot be empty.');
        }

        return $this->mailers[$name] ??= $this->createMailer($name);
    }

    public function setMailer(string $name, MailerInterface $mailer): void
    {
        if ($name === '') {
            throw new MailException('Mailer name cannot be empty.');
        }

        $this->mailers[$name] = $mailer;
    }

    /**
     * @return list<string>
     */
    public function mailers(): array
    {
        $mailers = $this->config['mailers'] ?? [];

        if (!is_array($mailers)) {
            return [];
        }

        return array_values(array_filter(array_keys($mailers), 'is_string'));
    }

    protected function createMailer(string $name): MailerInterface
    {
        $config = $this->mailerConfig($name);
        $transport = $config['transport'] ?? $name;
        $transport = is_string($transport) && $transport !== '' ? $transport : $name;

        return new Mailer($name, match ($transport) {
            'array' => new Transports\ArrayTransport($name),
            'log' => new Transports\LogTransport($this->logger ?? new NullLogger(), $name),
            'sendmail' => new Transports\SendmailTransport(
                $this->stringConfig($config, 'command', '/usr/sbin/sendmail -t -i'),
                $name,
            ),
            'smtp' => new Transports\SmtpTransport(
                $this->stringConfig($config, 'dsn', 'smtp://localhost:25'),
                $name,
            ),
            default => throw new MailException("Unsupported mail transport [{$transport}]."),
        });
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailerConfig(string $name): array
    {
        $mailers = $this->config['mailers'] ?? null;

        if (!is_array($mailers) || !isset($mailers[$name]) || !is_array($mailers[$name])) {
            throw new MailException("Mailer [{$name}] is not configured.");
        }

        $config = [];
        foreach ($mailers[$name] as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $config
     */
    protected function stringConfig(array $config, string $key, string $default): string
    {
        $value = $config[$key] ?? $default;

        return is_string($value) && $value !== '' ? $value : $default;
    }
}
