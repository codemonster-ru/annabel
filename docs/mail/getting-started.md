---
title: "Getting started"
description: "First standalone usage of codemonster-ru/mail"
order: 1
---

# Getting started

`codemonster-ru/mail` provides mail messages, mailers, and array, log, sendmail,
and SMTP transports.

## Basic usage

```php
use Codemonster\Mail\Mailer;
use Codemonster\Mail\Message;
use Codemonster\Mail\Transports\SendmailTransport;

$mailer = new Mailer('sendmail', new SendmailTransport());

$mailer->send(
    Message::make()
        ->from('hello@example.com', 'Example App')
        ->to('user@example.com')
        ->subject('Welcome')
        ->text('Welcome.')
);
```
