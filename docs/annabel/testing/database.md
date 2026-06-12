---
title: "Database"
description: "Database testing recommendations"
order: 4
---

# Database

Use isolated database configuration for tests.

```dotenv
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

SQLite is useful for fast tests when the SQL behavior is portable. Use MySQL or
another target database for integration tests that rely on driver-specific
behavior.

## In-memory SQLite

Use `:memory:` for tests that build schema for each test process:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Run migrations before assertions that depend on tables.

## Driver-specific tests

Use the target database for behavior that differs by driver:

- SQL grammar differences.
- Foreign key behavior.
- JSON column behavior.
- Locking and transaction semantics.

## Queue/database tables

When testing database-backed queues, publish and migrate queue tables in the
test setup or use `QUEUE_CONNECTION=sync` for tests that do not need queue
persistence.
