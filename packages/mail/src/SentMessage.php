<?php

namespace Codemonster\Mail;

class SentMessage
{
    public function __construct(
        protected string $id,
        protected string $mailer,
        protected string $transport,
        protected Message $message,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function mailer(): string
    {
        return $this->mailer;
    }

    public function transport(): string
    {
        return $this->transport;
    }

    public function message(): Message
    {
        return $this->message;
    }
}
