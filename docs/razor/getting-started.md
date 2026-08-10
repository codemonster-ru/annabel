---
title: "Getting started"
description: "Rendering server-side templates with codemonster-ru/razor"
order: 1
---

# Getting started

`codemonster-ru/razor` compiles Razor-like templates to PHP and renders them on
the server. Templates use the `.razor.php` extension.

## Basic usage

Create an engine with a view locator and an application-owned cache directory:

```php
use Codemonster\Razor\RazorEngine;
use Codemonster\View\Locator\DefaultLocator;

$engine = new RazorEngine(
    new DefaultLocator(__DIR__ . '/views'),
    cachePath: __DIR__ . '/storage/cache/views',
);

echo $engine->render('home', ['name' => 'Ada']);
```

In Annabel applications the engine is registered automatically. A template can
be rendered through the shared view service:

```php
echo view('home', ['name' => 'Ada']);
```

## Output

Double braces escape values for safe HTML output:

```razor
<h1>{{ $title }}</h1>
```

Use raw output only for trusted or previously sanitized HTML:

```razor
{!! $content !!}
```

Strings, numbers, booleans, `null`, and `Stringable` objects can be rendered.
Arrays and other unsupported values raise a `RazorException` instead of being
silently converted.

Use `@{{ ... }}` when another client-side template system must receive literal
double braces, and `@@` for a literal at sign:

```razor
@{{ clientExpression }}
support@@example.com
```

## Conditions and loops

Razor directives accept regular PHP expressions, including nested function
calls and closures:

```razor
@if ($user !== null && $user->isAdmin())
    <p>Administrator</p>
@elseif ($user !== null)
    <p>Member</p>
@else
    <p>Guest</p>
@endif

@foreach ($posts as $post)
    <a href="/posts/{{ $post->slug }}">{{ $post->title }}</a>
@endforeach
```

The engine also supports `@for`/`@endfor`, `@while`/`@endwhile`,
`@switch`/`@case`/`@default`/`@endswitch`, `@break`, and `@continue`.
Conditional `@break(...)` and `@continue(...)` are supported.

## Includes

Includes inherit the data originally passed to the current template. Explicit
data overrides inherited values:

```razor
@include('partials.header')
@include('partials.card', ['title' => $featuredTitle])
```

## Layouts and sections

A child template can extend one layout and define named sections:

```razor
@extends('layouts.default')

@section('title')
    {{ $metaTitle }}
@endsection

@section('content')
    <article>{!! $content !!}</article>
@endsection
```

The layout renders sections with `@yield`. The optional second argument is used
when the child does not define that section:

```razor
<!DOCTYPE html>
<html>
    <head>
        <title>@yield('title', 'Annabel')</title>
    </head>
    <body>
        @yield('content')
    </body>
</html>
```

Layouts may extend other layouts. Content outside a named section is exposed as
the implicit `content` section when the template extends a layout.

## Comments

Razor comments are omitted from rendered output:

```razor
{{-- This is visible only in the template source. --}}
```

## Cache behavior

Compiled templates are written atomically. Cache freshness is based on template
contents rather than modification time, so deployments that preserve timestamps
still recompile changed templates. Included templates and layouts are compiled
independently when rendered.
