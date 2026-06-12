---
title: "Mail"
description: "Sending mail through configured transports"
order: 6
---

# Mail

Annabel exposes mailers through `mailer()`.

## Send a message

```php
use Codemonster\Mail\Message;

mailer('log')->send(
    Message::make()
        ->from('hello@example.com', 'Annabel')
        ->to('user@example.com')
        ->subject('Welcome')
        ->text('Welcome to Annabel.'),
);
```

## Transports

Supported transports include `array`, `log`, `sendmail`, and `smtp`.

Configure mailers in `config/mail.php`.

```php
return [
    'default' => env('MAIL_MAILER', 'log'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Annabel'),
    ],
    'mailers' => [
        'array' => ['transport' => 'array'],
        'log' => ['transport' => 'log'],
        'smtp' => [
            'transport' => 'smtp',
            'dsn' => env('MAILER_DSN', 'smtp://localhost:25'),
        ],
        'sendmail' => [
            'transport' => 'sendmail',
            'command' => env('MAIL_SENDMAIL_COMMAND', '/usr/sbin/sendmail -t -i'),
        ],
    ],
];
```

## Choose a mailer

```php
mailer('log')->send($message);
mailer('smtp')->send($message);
```

To use the configured default mailer through the manager, call
`mailer()->mailer()->send($message)`.

## Message API

```php
Message::make()
    ->from('hello@example.com', 'Annabel')
    ->to('user@example.com', 'User')
    ->cc('copy@example.com')
    ->bcc('audit@example.com')
    ->replyTo('support@example.com')
    ->subject('Welcome')
    ->text('Plain text body')
    ->html('<p>HTML body</p>')
    ->header('X-App', 'Annabel');
```

A sendable message must have a sender, at least one recipient, and a text or
HTML body.

## Testing mail

Use the `array` mailer in tests so sent messages stay in memory:

```dotenv
MAIL_MAILER=array
```
