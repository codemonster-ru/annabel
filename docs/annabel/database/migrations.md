---
title: "Migrations"
description: "Creating and running database migrations"
order: 4
---

# Migrations

Migrations version database schema changes.

## Create a migration

```bash
php vendor/bin/annabel make:migration create_posts_table
```

Migration files live in `database/migrations` by default.

## Migration class

```php
use Codemonster\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        schema()->drop('posts');
    }
};
```

## Run migrations

```bash
php vendor/bin/annabel migrate
```

## Roll back

```bash
php vendor/bin/annabel migrate:rollback
```

## Status

```bash
php vendor/bin/annabel migrate:status
```

## Queue migrations

Database-backed queues need queue tables:

```bash
php vendor/bin/annabel vendor:publish --tag=queue-migrations
php vendor/bin/annabel migrate
```
