<?php

namespace Codemonster\Mail\Transports;

use Codemonster\Mail\Contracts\TransportInterface;
use Codemonster\Mail\Message;
use Codemonster\Mail\SentMessage;

class ArrayTransport implements TransportInterface
{
    /** @var list<SentMessage> */
    protected array $messages = [];

    public function __construct(protected string $name = 'array')
    {
    }

    public function send(Message $message): SentMessage
    {
        $message->ensureSendable();
        $sent = new SentMessage($this->id(), $this->name, 'array', $message);

        $this->messages[] = $sent;

        return $sent;
    }

    /**
     * @return list<SentMessage>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    protected function id(): string
    {
        return bin2hex(random_bytes(16));
    }
}
