<?php

namespace Codemonster\Mail;

class MimeRenderer
{
    public function render(Message $message): string
    {
        $message->ensureSendable();

        $headers = $this->headers($message);
        $body = $message->htmlBody() ?? $message->textBody() ?? '';

        if ($message->htmlBody() !== null && $message->textBody() !== null) {
            $boundary = 'annabel-' . bin2hex(random_bytes(12));
            $headers['MIME-Version'] = '1.0';
            $headers['Content-Type'] = 'multipart/alternative; boundary="' . $boundary . '"';
            $body = "--{$boundary}\r\n"
                . "Content-Type: text/plain; charset=UTF-8\r\n\r\n"
                . $message->textBody() . "\r\n"
                . "--{$boundary}\r\n"
                . "Content-Type: text/html; charset=UTF-8\r\n\r\n"
                . $message->htmlBody() . "\r\n"
                . "--{$boundary}--";
        } else {
            $headers['MIME-Version'] = '1.0';
            $headers['Content-Type'] = $message->htmlBody() !== null
                ? 'text/html; charset=UTF-8'
                : 'text/plain; charset=UTF-8';
        }

        return $this->renderHeaders($headers) . "\r\n\r\n" . $body;
    }

    /**
     * @return array<string, string>
     */
    protected function headers(Message $message): array
    {
        $headers = [
            'From' => $message->fromAddress()->formatted(),
            'To' => $this->formatAddresses($message->toAddresses()),
            'Subject' => $message->subjectLine(),
        ];

        if ($message->ccAddresses() !== []) {
            $headers['Cc'] = $this->formatAddresses($message->ccAddresses());
        }

        if ($message->bccAddresses() !== []) {
            $headers['Bcc'] = $this->formatAddresses($message->bccAddresses());
        }

        if ($message->replyToAddress()) {
            $headers['Reply-To'] = $message->replyToAddress()->formatted();
        }

        foreach ($message->headers() as $name => $value) {
            $headers[$name] = $value;
        }

        return $headers;
    }

    /**
     * @param list<Address> $addresses
     */
    protected function formatAddresses(array $addresses): string
    {
        return implode(', ', array_map(
            static fn (Address $address): string => $address->formatted(),
            $addresses,
        ));
    }

    /**
     * @param array<string, string> $headers
     */
    protected function renderHeaders(array $headers): string
    {
        $lines = [];

        foreach ($headers as $name => $value) {
            $lines[] = $name . ': ' . str_replace(["\r", "\n"], '', $value);
        }

        return implode("\r\n", $lines);
    }
}
