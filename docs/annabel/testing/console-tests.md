---
title: "Console tests"
description: "Testing console commands"
order: 3
---

# Console tests

Console commands are normal classes resolved through the container. Prefer
testing command behavior through the command class and using buffered output for
assertions.

Use integration tests for command registration and CLI wiring when a package or
application provider adds commands.

## Test command classes directly

Commands are regular classes. For command-level behavior, instantiate the
command with its dependencies and pass test input/output implementations.

```php
$command = new SyncCommand($service);

$exitCode = $command->execute($input, $output);

self::assertSame(0, $exitCode);
```

## Test registration separately

Provider tests should verify that the command is registered and can be resolved
from the application container. Keep command business behavior in command tests
so CLI wiring tests stay small.

## Prefer finite workers

When testing queue workers through CLI wiring, use finite modes:

```bash
php vendor/bin/annabel queue:work --once
php vendor/bin/annabel queue:work --stop-when-empty
```
