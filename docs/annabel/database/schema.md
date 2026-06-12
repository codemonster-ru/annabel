---
title: "Schema"
description: "Creating and changing database tables"
order: 3
---

# Schema

Use the schema builder to create and change tables.

## Create tables

Define new tables with the schema builder inside a migration.

```php
schema()->create('posts', function ($table) {
    $table->id();
    $table->string('title');
    $table->text('body')->nullable();
    $table->boolean('published')->default(false);
    $table->timestamps();
});
```

Schema changes normally belong in migrations.

## Column types

Choose column methods according to the data each field must store.

| Method | Purpose |
| --- | --- |
| `id(name = 'id')` | Auto-incrementing id column and primary key. |
| `string(name, length = 255)` | Variable-length string. |
| `char(name, length = 255)` | Fixed-length string. |
| `text(name)` | Text column. |
| `mediumText(name)` | Medium text column. |
| `longText(name)` | Long text column. |
| `integer(name)` | Integer column. |
| `bigInteger(name)` | Big integer column. |
| `mediumInteger(name)` | Medium integer column. |
| `smallInteger(name)` | Small integer column. |
| `tinyInteger(name)` | Tiny integer column. |
| `boolean(name)` | Boolean column. |
| `decimal(name, precision = 8, scale = 2)` | Decimal column. |
| `double(name, precision = 8, scale = 2)` | Double column. |
| `float(name, precision = 8, scale = 2)` | Float column. |
| `json(name)` | JSON column. |
| `date(name)` | Date column. |
| `datetime(name)` | Datetime column. |
| `timestamp(name)` | Timestamp column. |
| `time(name)` | Time column. |
| `year(name)` | Year column. |
| `uuid(name)` | UUID column. |
| `timestamps()` | Nullable `created_at` and `updated_at` timestamps. |

## Column modifiers

Chain modifiers after a column definition to add constraints or metadata.

```php
$table->string('email')->unique();
$table->string('nickname')->nullable();
$table->boolean('active')->default(true);
$table->integer('votes')->unsigned();
$table->text('bio')->comment('Public profile bio');
```

Available modifiers:

- `nullable(bool $value = true)`
- `default(mixed $value)`
- `unique()`
- `primary()`
- `autoIncrement()`
- `unsigned(bool $value = true)`
- `comment(string $comment)`
- `change()`

## Indexes

Add indexes for uniqueness, primary keys, or frequently queried columns.

```php
$table->index('email');
$table->unique(['team_id', 'slug']);
$table->primary('id');
```

## Foreign keys

Define foreign keys to enforce relationships between tables at the database
level.

```php
$table->bigInteger('user_id')->unsigned();

$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->cascadeOnDelete();
```

Foreign key helpers include `onDelete()`, `onUpdate()`,
`cascadeOnDelete()`, `restrictOnDelete()`, `nullOnDelete()`,
`cascadeOnUpdate()`, `restrictOnUpdate()`, and `nullOnUpdate()`.

## Alter tables

Use `table()` to add, rename, change, or remove columns on an existing table.

```php
schema()->table('posts', function ($table) {
    $table->string('summary')->nullable();
    $table->renameColumn('title', 'headline');
    $table->dropColumn('old_column');
});
```

Table helpers include `create()`, `table()`, `drop()`, and `dropIfExists()`.
