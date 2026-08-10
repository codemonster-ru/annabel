<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests;

use Codemonster\Razor\Exceptions\RazorException;
use Codemonster\Razor\RazorEngine;
use Codemonster\View\Locator\DefaultLocator;
use PHPUnit\Framework\TestCase;

final class RazorEngineTest extends TestCase
{
    private string $root;
    private string $views;
    private string $cache;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/annabel-razor-' . bin2hex(random_bytes(6));
        $this->views = $this->root . '/views';
        $this->cache = $this->root . '/cache';

        mkdir($this->views, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testRendersEscapedAndRawOutput(): void
    {
        $this->template('welcome', '<h1>{{ $user }}</h1>{!! $content !!}');

        $html = $this->engine()->render('welcome', [
            'user' => '<Ada & "Bob">',
            'content' => '<p>Trusted</p>',
        ]);

        self::assertSame('<h1>&lt;Ada &amp; &quot;Bob&quot;&gt;</h1><p>Trusted</p>', $html);
    }

    public function testRendersConditionsAndLoopsWithNestedExpressions(): void
    {
        $this->template('control', <<<'RAZOR'
@if (in_array(strtoupper($role), ['ADMIN', 'OWNER'], true))
allowed
@elseif ($role === 'guest')
guest
@else
denied
@endif
@foreach (array_filter($items, fn ($item) => ($item['visible'] ?? false)) as $item)
{{ $item['name'] }}
@endforeach
RAZOR);

        $html = $this->engine()->render('control', [
            'role' => 'admin',
            'items' => [
                ['name' => '<One>', 'visible' => true],
                ['name' => 'Two', 'visible' => false],
            ],
        ]);

        self::assertStringContainsString('allowed', $html);
        self::assertStringContainsString('&lt;One&gt;', $html);
        self::assertStringNotContainsString('denied', $html);
        self::assertStringNotContainsString('Two', $html);
    }

    public function testRendersForWhileSwitchBreakAndContinueDirectives(): void
    {
        $this->template('directives', <<<'RAZOR'
@for ($index = 0; $index < 4; $index++)
@continue($index === 1)
{{ $index }}
@break($index === 2)
@endfor
@while ($remaining > 0)
{{ $remaining-- }}
@endwhile
@switch ($status)
@case ('published')Published@break
@default Draft
@endswitch
RAZOR);

        $html = $this->engine()->render('directives', [
            'remaining' => 2,
            'status' => 'published',
        ]);

        self::assertSame('0221Published', $html);
        self::assertStringNotContainsString('Draft', $html);
    }

    public function testRendersIncludeWithInheritedAndExplicitData(): void
    {
        $this->template('page', "@include('partials.card', ['title' => 'Override'])");
        $this->template('partials.card', '{{ $title }} — {{ $site }}');

        $html = $this->engine()->render('page', [
            'title' => 'Original',
            'site' => 'Annabel',
        ]);

        self::assertSame('Override — Annabel', $html);
    }

    public function testRendersLayoutSectionsAndDefaults(): void
    {
        $this->template('layouts.default', <<<'RAZOR'
<html><title>@yield('title', 'Fallback')</title><body>@yield('content')<aside>@yield('sidebar', 'Default sidebar')</aside></body></html>
RAZOR);
        $this->template('page', <<<'RAZOR'
@extends('layouts.default')
@section('title'){{ $title }}@endsection
@section('content')<h1>{{ $title }}</h1>@endsection
RAZOR);

        $html = $this->engine()->render('page', ['title' => '<Welcome>']);

        self::assertSame(
            '<html><title>&lt;Welcome&gt;</title><body><h1>&lt;Welcome&gt;</h1><aside>Default sidebar</aside></body></html>',
            $html,
        );
    }

    public function testChildSectionsOverrideNestedLayoutDefaults(): void
    {
        $this->template('layouts.base', '<main>@yield(\'content\')</main>');
        $this->template('layouts.article', <<<'RAZOR'
@extends('layouts.base')
@section('content')<article>@yield('article')</article>@endsection
RAZOR);
        $this->template('page', <<<'RAZOR'
@extends('layouts.article')
@section('article')Hello@endsection
RAZOR);

        self::assertSame('<main><article>Hello</article></main>', $this->engine()->render('page'));
    }

    public function testUsesUnsectionedChildOutputAsContent(): void
    {
        $this->template('layout', '<main>@yield(\'content\')</main>');
        $this->template('page', "@extends('layout')<p>Hello</p>");

        self::assertSame('<main><p>Hello</p></main>', $this->engine()->render('page'));
    }

    public function testRejectsCircularLayoutInheritance(): void
    {
        $this->template('first', "@extends('second')");
        $this->template('second', "@extends('first')");

        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('Circular Razor layout inheritance: first -> second -> first');

        $this->engine()->render('first');
    }

    public function testIncludedTemplateCannotReplaceParentLayout(): void
    {
        $this->template('page', "@include('partial')");
        $this->template('partial', "@extends('layout')");
        $this->template('layout', 'Layout');

        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('An included Razor template cannot extend a layout.');

        $this->engine()->render('page');
    }

    public function testSupportsCommentsAndEscapedAtSigns(): void
    {
        $this->template('syntax', "{{-- hidden\ncomment --}}Email: user@@example.com @{{ clientValue }}");

        self::assertSame("\nEmail: user@example.com {{ clientValue }}", $this->engine()->render('syntax'));
    }

    public function testRecompilesTemplateWhenContentsChangeWithoutMtimeChange(): void
    {
        $path = $this->template('page', 'First');
        $engine = $this->engine();

        self::assertSame('First', $engine->render('page'));
        $mtime = filemtime($path);
        file_put_contents($path, 'Second');

        if ($mtime !== false) {
            touch($path, $mtime);
        }

        self::assertSame('Second', $engine->render('page'));
    }

    public function testReportsTemplateAndLineForMalformedDirective(): void
    {
        $path = $this->template('broken', "first\n@if (trim((string) \$value)\nlast");

        try {
            $this->engine()->render('broken', ['value' => 'x']);
            self::fail('Expected RazorException was not thrown.');
        } catch (RazorException $exception) {
            self::assertStringContainsString('Unclosed Razor directive arguments.', $exception->getMessage());
            self::assertStringContainsString($path . ':2', $exception->getMessage());
        }
    }

    public function testRejectsValuesThatCannotBeRendered(): void
    {
        $this->template('invalid', '{{ $items }}');

        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('Unable to render value of type [array].');

        $this->engine()->render('invalid', ['items' => []]);
    }

    public function testRestoresOutputBufferAfterRenderingFailure(): void
    {
        $this->template('broken', "@section('content'){{ \$items }}");
        $level = ob_get_level();

        try {
            $this->engine()->render('broken', ['items' => []]);
            self::fail('Expected RazorException was not thrown.');
        } catch (RazorException) {
            self::assertSame($level, ob_get_level());
        }
    }

    public function testExposesLocatorAndExtensionsForViewEngineDetection(): void
    {
        $engine = $this->engine();

        self::assertInstanceOf(DefaultLocator::class, $engine->getLocator());
        self::assertSame(['razor.php'], $engine->getExtensions());
    }

    private function engine(): RazorEngine
    {
        return new RazorEngine(new DefaultLocator($this->views), 'razor.php', $this->cache);
    }

    private function template(string $name, string $contents): string
    {
        $path = $this->views . '/' . str_replace('.', '/', $name) . '.razor.php';
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents);

        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);

        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
