<?php

namespace Codemonster\Mail\Contracts;

use Codemonster\Mail\Message;
use Codemonster\Mail\SentMessage;

interface MailerInterface
{
    public function send(Message $message): SentMessage;
}
