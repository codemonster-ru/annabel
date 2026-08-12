<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components\Compilation;

use Codemonster\Razor\Components\Compilation\ComponentInvocationCompiler;
use Codemonster\Razor\Components\Compilation\ComponentPropCompiler;
use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Parsing\ComponentInvocation;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use Codemonster\Razor\Components\Parsing\ComponentSlotParser;
use PHPUnit\Framework\TestCase;

final class ComponentInvocationCompilerTest extends TestCase
{
    private ComponentInvocationCompiler $compiler;

    protected function setUp(): void
    {
        $registry = new ComponentRegistry();
        $registry->registerPrefix('cm', []);
        $this->compiler = new ComponentInvocationCompiler(
            new ComponentPropCompiler(),
            new ComponentSlotParser(new ComponentParser($registry)),
        );
    }

    public function testCompilesPairedContentIntoALazyDefaultSlot(): void
    {
        $invocation = new ComponentInvocation(
            'cm-button',
            'variant="secondary"',
            'Save {{ $label }}',
            0,
            0,
        );
        $contentCompilations = 0;

        $compiled = $this->compiler->compile(
            $invocation,
            static function (string $content) use (&$contentCompilations): string {
                $contentCompilations++;

                return str_replace('{{ $label }}', '<?= $__razor->escape($label) ?>', $content);
            },
        );

        self::assertSame(1, $contentCompilations);
        self::assertStringContainsString("renderComponent('cm-button', ['variant' => 'secondary']", $compiled);
        self::assertStringContainsString("'default' => \$__razor->componentSlot(get_defined_vars()", $compiled);
        self::assertStringContainsString('Save <?= $__razor->escape($label) ?>', $compiled);
    }

    public function testOmitsDefaultSlotForSelfClosingComponents(): void
    {
        $invocation = new ComponentInvocation('cm-button', '', null, 0, 0);

        $compiled = $this->compiler->compile(
            $invocation,
            static fn (string $content): string => $content,
        );

        self::assertSame("<?= \$__razor->renderComponent('cm-button', [], []) ?>", $compiled);
    }

    public function testCompilesNamedSlotsSeparatelyFromDefaultContent(): void
    {
        $invocation = new ComponentInvocation(
            'cm-button',
            '',
            '<razor-slot name="leading">Lead</razor-slot>Label<razor-slot name="trailing">Trail</razor-slot>',
            0,
            0,
        );

        $compiled = $this->compiler->compile($invocation, static fn (string $content): string => $content);

        self::assertStringContainsString("'default' =>", $compiled);
        self::assertStringContainsString('?>Label<?php', $compiled);
        self::assertStringContainsString("'leading' =>", $compiled);
        self::assertStringContainsString('?>Lead<?php', $compiled);
        self::assertStringContainsString("'trailing' =>", $compiled);
        self::assertStringContainsString('?>Trail<?php', $compiled);
        self::assertStringNotContainsString('razor-slot', $compiled);
    }
}
