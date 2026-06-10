<?php

namespace Codemonster\Mail;

class Address
{
    public function __construct(
        protected string $email,
        protected ?string $name = null,
    ) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new MailException("Invalid email address [{$email}].");
        }
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function formatted(): string
    {
        if ($this->name === null || $this->name === '') {
            return $this->email;
        }

        return sprintf('"%s" <%s>', addcslashes($this->name, '"\\'), $this->email);
    }
}
