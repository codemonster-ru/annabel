<?php

namespace Codemonster\Mail\Transports;

use Codemonster\Mail\Contracts\TransportInterface;
use Codemonster\Mail\Message;
use Codemonster\Mail\SentMessage;
use Psr\Log\LoggerInterface;

class LogTransport implements TransportInterface
{
    public function __construct(
        protected LoggerInterface $logger,
        protected string $name = 'log',
    ) {
    }

    public function send(Message $message): SentMessage
    {
        $message->ensureSendable();
        $sent = new SentMessage($this->id(), $this->name, 'log', $message);

        $this->logger->info('Mail message sent.', [
            'id' => $sent->id(),
            'to' => array_map(static fn ($address): string => $address->email(), $message->toAddresses()),
            'subject' => $message->subjectLine(),
        ]);

        return $sent;
    }

    protected function id(): string
    {
        return bin2hex(random_bytes(16));
    }
}
