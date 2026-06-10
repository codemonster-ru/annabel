<?php

namespace Codemonster\Mail\Transports;

use Codemonster\Mail\Contracts\TransportInterface;
use Codemonster\Mail\MailException;
use Codemonster\Mail\Message;
use Codemonster\Mail\MimeRenderer;
use Codemonster\Mail\SentMessage;

class SendmailTransport implements TransportInterface
{
    public function __construct(
        protected string $command = '/usr/sbin/sendmail -t -i',
        protected string $name = 'sendmail',
        protected ?MimeRenderer $renderer = null,
    ) {
        $this->renderer ??= new MimeRenderer();
    }

    public function send(Message $message): SentMessage
    {
        $message->ensureSendable();
        $process = proc_open($this->command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            throw new MailException('Unable to start sendmail process.');
        }

        fwrite($pipes[0], $this->renderer()->render($message));
        fclose($pipes[0]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new MailException('Sendmail process failed: ' . trim((string) $error));
        }

        return new SentMessage($this->id(), $this->name, 'sendmail', $message);
    }

    protected function renderer(): MimeRenderer
    {
        if (!$this->renderer) {
            $this->renderer = new MimeRenderer();
        }

        return $this->renderer;
    }

    protected function id(): string
    {
        return bin2hex(random_bytes(16));
    }
}
