---
title: "Send queued mail"
description: "Dispatch a job that sends mail"
order: 4
---

# Send queued mail

This recipe dispatches a job that sends a welcome email.

## Configure queue and mail

Use `sync` locally or for tests:

```dotenv
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

Use a durable queue in production:

```dotenv
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

## Job

```bash
php vendor/bin/annabel make:job SendWelcomeEmailJob
```

```php
namespace App\Jobs;

use Codemonster\Mail\Message;
use Codemonster\Queue\Contracts\JobInterface;

final class SendWelcomeEmailJob implements JobInterface
{
    public function __construct(
        private string $email,
    ) {
    }

    public function handle(): void
    {
        mailer()->mailer()->send(
            Message::make()
                ->from('hello@example.com', 'Annabel')
                ->to($this->email)
                ->subject('Welcome')
                ->text('Welcome to Annabel.'),
        );
    }
}
```

## Dispatch

```php
dispatch(new SendWelcomeEmailJob($user->email));
```

## Worker

```bash
php vendor/bin/annabel queue:work
```

For database queues, publish and run queue migrations before starting workers.
