<?php

namespace Codemonster\Mail\Tests;

use Codemonster\Mail\MailException;
use Codemonster\Mail\MailManager;
use Codemonster\Mail\Message;
use Codemonster\Mail\MimeRenderer;
use Codemonster\Mail\Transports\ArrayTransport;
use Codemonster\Mail\Transports\SmtpTransport;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class MailTest extends TestCase
{
    public function test_array_transport_collects_sent_messages(): void
    {
        $transport = new ArrayTransport();
        $sent = $transport->send($this->message());

        self::assertNotSame('', $sent->id());
        self::assertSame('array', $sent->transport());
        self::assertCount(1, $transport->messages());
    }

    public function test_manager_resolves_configured_mailers(): void
    {
        $manager = new MailManager([
            'default' => 'array',
            'mailers' => [
                'array' => [
                    'transport' => 'array',
                ],
            ],
        ]);

        $sent = $manager->mailer()->send($this->message());

        self::assertSame('array', $sent->mailer());
        self::assertSame(['array'], $manager->mailers());
    }

    public function test_log_transport_writes_summary(): void
    {
        $logger = new TestLogger();
        $manager = new MailManager([
            'default' => 'log',
            'mailers' => [
                'log' => [
                    'transport' => 'log',
                ],
            ],
        ], $logger);

        $manager->mailer()->send($this->message());

        self::assertSame('Mail message sent.', $logger->records[0]['message']);
        self::assertSame('Welcome', $logger->records[0]['context']['subject']);
    }

    public function test_message_requires_sender_recipient_and_body(): void
    {
        $this->expectException(MailException::class);

        Message::make()->subject('Broken')->ensureSendable();
    }

    public function test_mime_renderer_renders_headers_and_body(): void
    {
        $mime = (new MimeRenderer())->render($this->message()->html('<strong>Welcome</strong>'));

        self::assertStringContainsString('From: "Annabel" <hello@example.com>', $mime);
        self::assertStringContainsString('To: user@example.com', $mime);
        self::assertStringContainsString('Subject: Welcome', $mime);
        self::assertStringContainsString('multipart/alternative', $mime);
    }

    public function test_smtp_transport_builds_and_sends_symfony_email(): void
    {
        $symfonyTransport = new TestSymfonyTransport();
        $transport = new SmtpTransport('smtp://localhost:25', transport: $symfonyTransport);
        $message = $this->message()
            ->cc('copy@example.com', 'Copy')
            ->bcc('hidden@example.com')
            ->replyTo('reply@example.com')
            ->html('<strong>Welcome</strong>')
            ->header('X-Application', 'Annabel');

        $sent = $transport->send($message);
        $email = $symfonyTransport->message;

        self::assertSame('smtp', $sent->transport());
        self::assertNotNull($email);
        self::assertSame('hello@example.com', $email->getFrom()[0]->getAddress());
        self::assertSame('Annabel', $email->getFrom()[0]->getName());
        self::assertSame('user@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('copy@example.com', $email->getCc()[0]->getAddress());
        self::assertSame('hidden@example.com', $email->getBcc()[0]->getAddress());
        self::assertSame('reply@example.com', $email->getReplyTo()[0]->getAddress());
        self::assertSame('Welcome', $email->getSubject());
        self::assertSame('Welcome to Annabel.', $email->getTextBody());
        self::assertSame('<strong>Welcome</strong>', $email->getHtmlBody());
        self::assertSame('Annabel', $email->getHeaders()->get('X-Application')?->getBodyAsString());
    }

    public function test_manager_resolves_smtp_transport_from_dsn(): void
    {
        $manager = new MailManager([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
                    'transport' => 'smtp',
                    'dsn' => 'null://null',
                ],
            ],
        ]);

        $sent = $manager->mailer()->send($this->message());

        self::assertSame('smtp', $sent->transport());
        self::assertNotSame('', $sent->id());
    }

    private function message(): Message
    {
        return Message::make()
            ->from('hello@example.com', 'Annabel')
            ->to('user@example.com')
            ->subject('Welcome')
            ->text('Welcome to Annabel.');
    }
}

class TestLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string|\Stringable, context: array<string, mixed>}> */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $normalizedContext = [];
        foreach ($context as $key => $value) {
            if (is_string($key)) {
                $normalizedContext[$key] = $value;
            }
        }

        $this->records[] = [
            'level' => $level,
            'message' => $message,
            'context' => $normalizedContext,
        ];
    }
}

class TestSymfonyTransport implements SymfonyTransportInterface
{
    public ?Email $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SymfonySentMessage
    {
        if (!$message instanceof Email) {
            throw new \RuntimeException('Expected a Symfony Email instance.');
        }

        $this->message = $message;

        return new SymfonySentMessage($message, $envelope ?? Envelope::create($message));
    }

    public function __toString(): string
    {
        return 'test://';
    }
}
