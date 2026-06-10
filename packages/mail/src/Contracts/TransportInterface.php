<?php

namespace Codemonster\Mail\Contracts;

use Codemonster\Mail\Message;
use Codemonster\Mail\SentMessage;

interface TransportInterface
{
    public function send(Message $message): SentMessage;
}
