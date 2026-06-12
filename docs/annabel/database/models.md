---
title: "Models"
description: "Lightweight model layer"
order: 6
---

# Models

Annabel's database package includes a lightweight model layer for common active
record style workflows.

## Create a model

```bash
php vendor/bin/annabel make:model User
```

## Define a model

```php
namespace App\Models;

use Codemonster\Database\ORM\Model;

final class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    protected array $hidden = [
        'password',
    ];

    protected array $casts = [
        'id' => 'int',
        'active' => 'bool',
        'settings' => 'array',
    ];
}
```

By default, the table name is inferred from the class name, the primary key is
`id`, incrementing keys are enabled, and timestamps use `created_at` and
`updated_at`.

## Query models

```php
$users = User::query()
    ->where('active', 1)
    ->get();
```

Use the query builder directly when you need explicit SQL-oriented control.

## Find and create

```php
$user = User::find(1);

$user = User::create([
    'name' => 'Annabel',
    'email' => 'hello@example.com',
]);
```

Mass assignment respects `$fillable` and `$guarded`.

## Save and delete

```php
$user = new User();
$user->name = 'Annabel';
$user->email = 'hello@example.com';
$user->save();

$user->delete();
```

## Casts

Supported casts include:

- `int` / `integer`
- `real` / `float` / `double`
- `string`
- `bool` / `boolean`
- `array`
- `json`
- `object`
- `datetime` / `immutable_datetime`
- `date`
- `decimal:2`

## Accessors and mutators

```php
protected function getNameAttribute(string $value): string
{
    return trim($value);
}

protected function setEmailAttribute(string $value): string
{
    return strtolower($value);
}
```

## Relationships

```php
public function posts()
{
    return $this->hasMany(Post::class);
}

public function team()
{
    return $this->belongsTo(Team::class);
}
```

Supported relationship helpers are `hasOne()`, `hasMany()`, `belongsTo()`, and
`belongsToMany()`.

## Eager loading

```php
$users = User::query()
    ->with('posts')
    ->get();

$user = User::find(1);
$user?->load('posts');
```

## Scopes

```php
public function scopeActive($query)
{
    return $query->where('active', 1);
}

$users = User::query()->active()->get();
```

## Soft deletes

```php
use Codemonster\Database\Traits\SoftDeletes;

final class Post extends Model
{
    use SoftDeletes;
}
```

Soft-delete helpers include `trashed()`, `restore()`, `withTrashed()`,
`withoutTrashed()`, and `onlyTrashed()`.

## Model events

```php
User::on('saving', function (User $user): bool {
    return $user->email !== '';
});

User::observe(UserObserver::class);
```

Listeners may return `false` to halt events that run before persistence.
