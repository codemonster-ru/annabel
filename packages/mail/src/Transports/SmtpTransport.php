<?php

namespace Codemonster\Mail\Transports;

use Codemonster\Mail\Address;
use Codemonster\Mail\Contracts\TransportInterface;
use Codemonster\Mail\MailException;
use Codemonster\Mail\Message;
use Codemonster\Mail\SentMessage;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface as SymfonyTransportInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email;

class SmtpTransport implements TransportInterface
{
    public function __construct(
        protected string $dsn,
        protected string $name = 'smtp',
        protected ?SymfonyTransportInterface $transport = null,
    ) {
        if ($dsn === '') {
            throw new MailException('SMTP DSN cannot be empty.');
        }
    }

    public function send(Message $message): SentMessage
    {
        $message->ensureSendable();

        try {
            $sent = $this->transport()->send($this->email($message));
        } catch (TransportExceptionInterface $e) {
            throw new MailException('SMTP delivery failed: ' . $e->getMessage(), previous: $e);
        }

        return new SentMessage(
            $sent?->getMessageId() ?? bin2hex(random_bytes(16)),
            $this->name,
            'smtp',
            $message,
        );
    }

    protected function transport(): SymfonyTransportInterface
    {
        if ($this->transport) {
            return $this->transport;
        }

        try {
            return $this->transport = Transport::fromDsn($this->dsn);
        } catch (\Throwable $e) {
            throw new MailException('Invalid SMTP DSN: ' . $e->getMessage(), previous: $e);
        }
    }

    protected function email(Message $message): Email
    {
        $email = (new Email())
            ->from($this->address($message->fromAddress()))
            ->to(...$this->addresses($message->toAddresses()))
            ->subject($message->subjectLine());

        if ($message->ccAddresses() !== []) {
            $email->cc(...$this->addresses($message->ccAddresses()));
        }

        if ($message->bccAddresses() !== []) {
            $email->bcc(...$this->addresses($message->bccAddresses()));
        }

        if ($message->replyToAddress()) {
            $email->replyTo($this->address($message->replyToAddress()));
        }

        if ($message->textBody() !== null) {
            $email->text($message->textBody());
        }

        if ($message->htmlBody() !== null) {
            $email->html($message->htmlBody());
        }

        foreach ($message->headers() as $name => $value) {
            $email->getHeaders()->addTextHeader($name, $value);
        }

        return $email;
    }

    protected function address(Address $address): SymfonyAddress
    {
        return new SymfonyAddress($address->email(), $address->name() ?? '');
    }

    /**
     * @param list<Address> $addresses
     * @return list<SymfonyAddress>
     */
    protected function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): SymfonyAddress => $this->address($address), $addresses);
    }
}
