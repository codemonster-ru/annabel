<?php

namespace Codemonster\Mail;

use Codemonster\Mail\Contracts\MailerInterface;
use Codemonster\Mail\Contracts\TransportInterface;

class Mailer implements MailerInterface
{
    public function __construct(
        protected string $name,
        protected TransportInterface $transport,
    ) {
    }

    public function send(Message $message): SentMessage
    {
        $sent = $this->transport->send($message);

        return new SentMessage($sent->id(), $this->name, $sent->transport(), $message);
    }
}
