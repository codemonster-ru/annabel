<?php

namespace Codemonster\Mail;

class Message
{
    protected ?Address $from = null;
    /** @var list<Address> */
    protected array $to = [];
    /** @var list<Address> */
    protected array $cc = [];
    /** @var list<Address> */
    protected array $bcc = [];
    protected ?Address $replyTo = null;
    protected string $subject = '';
    protected ?string $text = null;
    protected ?string $html = null;
    /** @var array<string, string> */
    protected array $headers = [];

    public static function make(): self
    {
        return new self();
    }

    public function from(string $email, ?string $name = null): self
    {
        $this->from = new Address($email, $name);

        return $this;
    }

    public function to(string $email, ?string $name = null): self
    {
        $this->to[] = new Address($email, $name);

        return $this;
    }

    public function cc(string $email, ?string $name = null): self
    {
        $this->cc[] = new Address($email, $name);

        return $this;
    }

    public function bcc(string $email, ?string $name = null): self
    {
        $this->bcc[] = new Address($email, $name);

        return $this;
    }

    public function replyTo(string $email, ?string $name = null): self
    {
        $this->replyTo = new Address($email, $name);

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function html(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        if (!preg_match('/^[A-Za-z0-9-]+$/', $name)) {
            throw new MailException("Invalid mail header name [{$name}].");
        }

        $this->headers[$name] = $value;

        return $this;
    }

    public function fromAddress(): Address
    {
        if (!$this->from) {
            throw new MailException('Mail message requires a sender address.');
        }

        return $this->from;
    }

    /**
     * @return list<Address>
     */
    public function toAddresses(): array
    {
        return $this->to;
    }

    /**
     * @return list<Address>
     */
    public function ccAddresses(): array
    {
        return $this->cc;
    }

    /**
     * @return list<Address>
     */
    public function bccAddresses(): array
    {
        return $this->bcc;
    }

    public function replyToAddress(): ?Address
    {
        return $this->replyTo;
    }

    public function subjectLine(): string
    {
        return $this->subject;
    }

    public function textBody(): ?string
    {
        return $this->text;
    }

    public function htmlBody(): ?string
    {
        return $this->html;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function ensureSendable(): void
    {
        $this->fromAddress();

        if ($this->to === [] && $this->cc === [] && $this->bcc === []) {
            throw new MailException('Mail message requires at least one recipient.');
        }

        if ($this->text === null && $this->html === null) {
            throw new MailException('Mail message requires a text or HTML body.');
        }
    }
}
