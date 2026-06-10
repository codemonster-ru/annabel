# Codemonster Mail

Mail message and transport primitives for Annabel applications.

## Usage

```php
use Codemonster\Mail\MailManager;
use Codemonster\Mail\Message;

$manager = new MailManager([
    'default' => 'array',
    'mailers' => [
        'array' => [
            'transport' => 'array',
        ],
    ],
]);

$manager->mailer()->send(
    Message::make()
        ->from('hello@example.com', 'Annabel')
        ->to('user@example.com')
        ->subject('Welcome')
        ->text('Welcome to Annabel.'),
);
```

The package ships with `array`, `log`, `sendmail`, and Symfony-powered `smtp`
transports:

```php
$manager = new MailManager([
    'default' => 'smtp',
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'dsn' => 'smtp://user:password@mail.example.com:587',
        ],
    ],
]);
```

Credentials containing reserved URI characters must be URL-encoded.
