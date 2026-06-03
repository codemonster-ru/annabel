> [!IMPORTANT]
> This repository is read-only.
>
> Development happens in the Annabel monorepo:
> https://github.com/codemonster-ru/annabel
>
> Issues and pull requests should be opened there.

# codemonster-ru/database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Total Downloads](https://img.shields.io/packagist/dt/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![License](https://img.shields.io/packagist/l/codemonster-ru/database.svg?style=flat-square)](https://packagist.org/packages/codemonster-ru/database)
[![Tests](https://github.com/codemonster-ru/database/actions/workflows/tests.yml/badge.svg)](https://github.com/codemonster-ru/database/actions/workflows/tests.yml)

A lightweight, framework-agnostic database layer for PHP.  
Part of the Codemonster ecosystem — but works fully standalone.

## Installation

```bash
composer require codemonster-ru/database
```

## Usage

### 1. Database Manager

```php
use Codemonster\Database\DatabaseManager;

$manager = new DatabaseManager([
    'default' => 'mysql', // name of the default connection
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset'  => 'utf8mb4',
        ],
    ],
]);

$db = $manager->connection(); // default connection
```

You can define multiple connections and select them by name:

```php
$manager = new DatabaseManager([
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'   => 'mysql',
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'database' => 'app',
            'username' => 'root',
            'password' => '',
        ],
        'sqlite' => [
            'driver'   => 'sqlite',
            'database' => __DIR__ . '/database.sqlite',
        ],
    ],
]);

$mysql  = $manager->connection();          // default (mysql)
$sqlite = $manager->connection('sqlite');  // explicit connection
```

-   For **MySQL/MariaDB** use `driver => 'mysql'`.
-   For **SQLite** use `driver => 'sqlite'` and only `database` is required (file path or `:memory:`).
-   Other PDO drivers can be wired via `driver` + DSN-compatible options; the query layer is driver-agnostic, while the schema builder is primarily tuned for MySQL-like syntax and SQLite.

### 2. Query Builder

#### SELECT

```php
$users = $db->table('users')
    ->select('id', 'name', 'email')
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

#### SELECT with aliases

```php
$rows = $db->table('users')
    ->select('users.name label', 'COUNT(*) total')
    ->groupBy('users.name')
    ->get();
```

#### INSERT

```php
$db->table('users')->insert([
    'name'  => 'Vasya',
    'email' => 'test@example.com',
]);

$id = $db->table('ideas')->insertGetId([
    'title' => 'New idea',
]);
```

#### UPDATE

```php
$db->table('users')
    ->where('id', 5)
    ->update([
        'active'     => 0,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
```

#### DELETE

```php
$db->table('sessions')
    ->where('user_id', 10)
    ->delete();
```

#### Debug SQL

```php
[$sql, $bindings] = $db->table('users')
    ->where('active', 1)
    ->toSql();

// $sql      = 'SELECT * FROM `users` WHERE `active` = ?'
// $bindings = [1]
```

#### Raw expressions

```php
$db->table('users')
    ->selectRaw('COUNT(*) as total')
    ->whereRaw('JSON_VALID(metadata)')
    ->orderByRaw('FIELD(status, "new", "approved", "archived")')
    ->get();
```

#### Join support

```php
$db->table('orders')
    ->join('users', 'users.id', '=', 'orders.user_id')
    ->leftJoin('payments', function ($join) {
        $join->on('payments.order_id', '=', 'orders.id')
             ->where('payments.status', 'paid');
    })
    ->get();
```

#### Group By / Having

```php
$db->table('orders')
    ->selectRaw('status, COUNT(*) as total')
    ->groupBy('status')
    ->having('total', '>', 10)
    ->get();
```

#### Aggregates

```php
$count = $db->table('users')->count();
$sum   = $db->table('orders')->sum('amount');
$avg   = $db->table('ratings')->avg('score');
$min   = $db->table('logs')->min('id');
$max   = $db->table('visits')->max('duration');
```

#### Exists

```php
$exists = $db->table('users')
    ->where('email', 'test@example.com')
    ->exists();
```

#### Value / Pluck

```php
$email = $db->table('users')
    ->where('id', 1)
    ->value('email');

$names = $db->table('users')->pluck('name');
$pairs = $db->table('users')->pluck('email', 'id'); // [id => email]

// Aliases are supported:
// $db->table('users')->pluck('users.name label', 'users.id key');
// $db->table('users')->value('COUNT(*) total');
```

#### Value / Pluck with aliases

```php
$pairs = $db->table('users')
    ->pluck('users.name label', 'users.id key'); // [id => name]

$total = $db->table('users')->value('COUNT(*) total');
```

#### Pagination

```php
$currentPage = 1;

$page = $db->table('posts')->simplePaginate(20, $currentPage);

// $page = [
//     'data'        => [...],
//     'per_page'    => 20,
//     'current_page'=> 1,
//     'next_page'   => 2,
//     'prev_page'   => null,
// ];
```

#### Empty whereIn / whereNotIn

```php
$db->table('users')
    ->setEmptyWhereInBehavior(\Codemonster\Database\Query\QueryBuilder::EMPTY_CONDITION_EXCEPTION)
    ->whereIn('id', []);
```

Available behaviors:

-   `EMPTY_CONDITION_NONE` (default for `whereIn`) -> executes `0 = 1`
-   `EMPTY_CONDITION_ALL` (default for `whereNotIn`) -> executes `1 = 1`
-   `EMPTY_CONDITION_EXCEPTION` -> throws `InvalidArgumentException`

You can override `whereNotIn` separately:

```php
$db->table('users')
    ->setEmptyWhereNotInBehavior(\Codemonster\Database\Query\QueryBuilder::EMPTY_CONDITION_NONE)
    ->whereNotIn('id', []);
```

### 3. Transactions

```php
$db->transaction(function ($db) {
    $db->table('users')->insert([
        'name'  => 'New user',
        'email' => 'user@example.com',
    ]);

    $db->table('logs')->insert([
        'message' => 'User created',
    ]);
});
```

### 4. Global Helpers (with `codemonster-ru/support`)

If you also install [`codemonster-ru/support`](https://packagist.org/packages/codemonster-ru/support)
and register bindings in your container, you can use global helpers:

```php
db();                 // returns default ConnectionInterface
db('sqlite');         // specific connection
schema();             // schema builder for default connection
transaction(fn() =>   // convenience wrapper
    db()->table('logs')->insert(['message' => 'ok'])
);
```

Helpers are thin wrappers around `DatabaseManager` and the connection’s `schema()` / `transaction()` methods.

## Schema Builder

The package includes a lightweight schema builder.

> **Note:** The schema grammar is focused on MySQL/MariaDB and SQLite.  
> For other PDO drivers, the query builder will work, but schema operations may not be fully supported.

### Creating a table

```php
use Codemonster\Database\Schema\Blueprint;

// You can also use Schema::forConnection($db) if you need a schema instance directly.
$db->schema()->create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->boolean('active')->default(1);
    $table->timestamps();
});
```

### Modifying a table

```php
$db->schema()->table('users', function (Blueprint $table) {
    $table->string('avatar')->nullable();
    $table->integer('age')->default(0);
});
```

### SQLite notes

-   SQLite supports `ALTER TABLE` only for a subset of operations; some drop operations are ignored.
-   Foreign keys are emitted inline during `CREATE TABLE`.

### Dropping a table

```php
$db->schema()->drop('users');

// or:
$db->schema()->dropIfExists('users');
```

## Supported Column Types

-   Integers: `id`, `integer`, `bigInteger`, `mediumInteger`, `smallInteger`, `tinyInteger`
-   Floats: `decimal`, `double`, `float`
-   Text: `string`, `char`, `text`, `mediumText`, `longText`
-   Boolean: `boolean`
-   JSON: `json`
-   Dates & time: `date`, `datetime`, `timestamp`, `time`, `year`
-   UUID: `uuid`
-   Indexes: `index`, `unique`, `primary`
-   Foreign keys with `foreign()` / `references()` / `on()` and `onDelete()` / `onUpdate()` helpers

## Migrations

The package includes a migration system (designed to be used via the CLI).

-   `migrate`
-   `migrate:rollback`
-   `migrate:status`
-   `make:migration`
-   `seed`
-   `make:seed`

### Example migration

```php
use Codemonster\Database\Migrations\Migration;
use Codemonster\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
    }

    public function down(): void
    {
        schema()->drop('posts');
    }
};
```

## Seeders

The package includes a lightweight seeding system (via the CLI).

### Example seeder

```php
use Codemonster\Database\Seeders\Seeder;

return new class extends Seeder {
    public function run(): void
    {
        db()->table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
    }
};
```

## ORM (ActiveRecord / Eloquent‑style)

**Since 1.3.0**, the package includes a complete ORM layer:

-   `Model`
-   `ModelQuery`
-   `ModelCollection`
-   Lazy & eager loading
-   `$fillable`, `$guarded`, `$hidden`
-   Attribute casting (`int`, `json`, `datetime`, etc.)
-   `created_at` / `updated_at`
-   `SoftDeletes`

### Example model

```php
use Codemonster\Database\ORM\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = ['name', 'email', 'password'];

    protected array $hidden = ['password'];

    protected array $casts = [
        'created_at' => 'datetime',
    ];
}
```

### Fetching models

```php
$user = User::find(1);

$active = User::query()
    ->where('active', 1)
    ->orderBy('id')
    ->get();
```

### Creating / updating / deleting

```php
User::create([
    'name' => 'John',
    'email' => 'john@example.com',
]);

$user->email = 'new@example.com';
$user->save();

$user->delete();
```

## Relationships

Available relations:

-   `HasOne`
-   `HasMany`
-   `BelongsTo`
-   `BelongsToMany`

### Example

```php
class User extends Model {
    public function posts() {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model {
    public function author() {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

### Lazy loading

```php
$user->posts;
```

### Eager loading

```php
$user->load('posts');
```

## Soft Deletes

```php
use Codemonster\Database\Traits\SoftDeletes;

class User extends Model {
    use SoftDeletes;
}
```

-   `$user->delete()` → sets `deleted_at`
-   `$user->restore()`
-   `User::onlyTrashed()`
-   `User::withTrashed()`

## CLI Tool

A standalone CLI ships with the package:

```bash
vendor/bin/database
```

### Running migrations

```bash
vendor/bin/database migrate
```

### Rollback

```bash
vendor/bin/database migrate:rollback
```

### Status

```bash
vendor/bin/database migrate:status
```

### Wipe database

```bash
vendor/bin/database db:wipe
```

Force wipe without confirmation:

```bash
vendor/bin/database db:wipe --force
```

### Clean database data (keep migrations table)

```bash
vendor/bin/database db:truncate
```

Force clean without confirmation:

```bash
vendor/bin/database db:truncate --force
```

### Create a migration

```bash
vendor/bin/database make:migration CreatePostsTable
```

Migration names must be CamelCase using only Latin letters (e.g., `CreateUsersTable`). Names that include other symbols or casing styles are rejected.

Default migrations directory:

```text
./database/migrations
```

You can override paths via the migration kernel/path resolver:

```php
$kernel->getPathResolver()->addPath('/path/to/migrations');
```

### Running seeders

```bash
vendor/bin/database seed
```

### Create a seeder

```bash
vendor/bin/database make:seed UsersSeeder
```

Seed names must be CamelCase using only Latin letters (e.g., `UsersSeeder`). Names that include other symbols or casing styles are rejected.

Default seeds directory:

```text
./database/seeds
```

You can override paths via the seed kernel/path resolver:

```php
$kernel->getSeedPathResolver()->addPath('/path/to/seeds');
```


## Tests

```bash
composer test
```

## Author

[**Kirill Kolesnikov**](https://github.com/KolesnikovKirill)

## License

[MIT](https://github.com/codemonster-ru/database/blob/main/LICENSE)
