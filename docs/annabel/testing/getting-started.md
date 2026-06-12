---
title: "Getting started"
description: "Testing Annabel applications"
order: 1
---

# Getting started

Annabel provides lightweight HTTP testing helpers for application tests.

## Feature test

```php
use Codemonster\Annabel\Application;
use Codemonster\Annabel\Testing\InteractsWithApplication;
use PHPUnit\Framework\TestCase;

final class HomeTest extends TestCase
{
    use InteractsWithApplication;

    protected function createApplication(): Application
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    public function test_homepage_is_available(): void
    {
        $this->get('/')->assertOk()->assertSee('Welcome');
    }
}
```

## Test environment

Use isolated drivers for application tests:

```dotenv
APP_ENV=testing
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

Use `refreshApplication()` when a test changes global application state and
needs a fresh container.
