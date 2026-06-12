---
title: "Build a CRUD resource"
description: "Create a simple posts CRUD flow"
order: 1
---

# Build a CRUD resource

This recipe creates a simple `posts` resource with a migration, model,
controller, routes, validation, and PHP views.

## Migration

```bash
php vendor/bin/annabel make:migration create_posts_table
```

```php
use Codemonster\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        schema()->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        schema()->dropIfExists('posts');
    }
};
```

Run it:

```bash
php vendor/bin/annabel migrate
```

## Model

```bash
php vendor/bin/annabel make:model Post
```

```php
namespace App\Models;

use Codemonster\Database\ORM\Model;

final class Post extends Model
{
    protected array $fillable = ['title', 'body'];
}
```

## Controller

```bash
php vendor/bin/annabel make:controller PostController
```

```php
namespace App\Controllers;

use App\Models\Post;
use Codemonster\Annabel\Http\ValidatesRequests;
use Codemonster\Http\Request;

final class PostController
{
    use ValidatesRequests;

    public function index(): mixed
    {
        return view('posts/index', [
            'posts' => Post::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function create(): mixed
    {
        return view('posts/create');
    }

    public function store(Request $request): mixed
    {
        $data = $this->validate($request, [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
        ]);

        Post::create($data);

        return response()->redirect('/posts');
    }

    public function show(string $id): mixed
    {
        return view('posts/show', [
            'post' => Post::find($id),
        ]);
    }
}
```

## Routes

```php
use App\Controllers\PostController;

$app->get('/posts', [PostController::class, 'index']);
$app->get('/posts/create', [PostController::class, 'create']);
$app->post('/posts', [PostController::class, 'store']);
$app->get('/posts/{id}', [PostController::class, 'show']);
```

Use controller handlers so the routes can be cached in production.
