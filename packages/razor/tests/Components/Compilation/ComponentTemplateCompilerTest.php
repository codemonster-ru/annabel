<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components\Compilation;

use Codemonster\Razor\Components\Compilation\ComponentInvocationCompiler;
use Codemonster\Razor\Components\Compilation\ComponentPropCompiler;
use Codemonster\Razor\Components\Compilation\ComponentTemplateCompiler;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use Codemonster\Razor\Components\Parsing\ComponentSlotParser;
use PHPUnit\Framework\TestCase;

final class ComponentTemplateCompilerTest extends TestCase
{
    public function testRecursivelyCompilesNestedComponentsAndOrdinaryRazor(): void
    {
        $registry = new ComponentRegistry();
        $registry->registerPrefix('cm', []);
        $parser = new ComponentParser($registry);
        $compiler = new ComponentTemplateCompiler(
            $parser,
            new ComponentInvocationCompiler(
                new ComponentPropCompiler(),
                new ComponentSlotParser($parser),
            ),
        );

        $compiled = $compiler->compile(
            'Before {{ $title }}<cm-card><razor-slot name="header"><cm-button>Open</cm-button></razor-slot>Body</cm-card>After',
            static fn (string $source): string => str_replace('{{ $title }}', '<?= escape-title ?>', $source),
        );

        self::assertSame(2, substr_count($compiled, 'renderComponent('));
        self::assertStringContainsString("renderComponent('cm-card'", $compiled);
        self::assertStringContainsString("renderComponent('cm-button'", $compiled);
        self::assertStringContainsString("'header' =>", $compiled);
        self::assertStringContainsString('Before <?= escape-title ?>', $compiled);
        self::assertStringEndsWith('After', $compiled);
    }

    public function testLeavesUnregisteredCustomElementsForOrdinaryCompilation(): void
    {
        $registry = new ComponentRegistry();
        $parser = new ComponentParser($registry);
        $compiler = new ComponentTemplateCompiler(
            $parser,
            new ComponentInvocationCompiler(new ComponentPropCompiler(), new ComponentSlotParser($parser)),
        );

        self::assertSame(
            '<other-card>Native custom element</other-card>',
            $compiler->compile('<other-card>Native custom element</other-card>', static fn (string $source): string => $source),
        );
    }
}
