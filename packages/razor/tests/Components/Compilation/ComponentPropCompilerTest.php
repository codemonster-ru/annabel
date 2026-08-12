<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components\Compilation;

use Codemonster\Razor\Components\Compilation\ComponentPropCompiler;
use Codemonster\Razor\Exceptions\RazorException;
use PHPUnit\Framework\TestCase;

final class ComponentPropCompilerTest extends TestCase
{
    private ComponentPropCompiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ComponentPropCompiler();
    }

    public function testCompilesStaticExpressionAndBooleanPropsInOrder(): void
    {
        $saving = true;
        $compiled = $this->compiler->compile('variant="secondary" :disabled="$saving" autofocus');

        /** @var array<string, mixed> $props */
        $props = eval('return ' . $compiled . ';');

        self::assertSame(
            ['variant' => 'secondary', 'disabled' => true, 'autofocus' => true],
            $props,
        );
    }

    public function testDecodesHtmlEntitiesInStaticProps(): void
    {
        $compiled = $this->compiler->compile('title="Ada &amp; Bob &quot;team&quot;"');

        /** @var array<string, mixed> $props */
        $props = eval('return ' . $compiled . ';');

        self::assertSame('Ada & Bob "team"', $props['title']);
    }

    /** @dataProvider invalidPropsProvider */
    public function testRejectsMalformedProps(string $attributes, string $message): void
    {
        $this->expectException(RazorException::class);
        $this->expectExceptionMessage($message);

        $this->compiler->compile($attributes);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidPropsProvider(): iterable
    {
        yield 'duplicate' => ['disabled disabled', 'Duplicate'];
        yield 'unquoted' => ['variant=primary', 'quoted value'];
        yield 'empty expression' => [':disabled=""', 'must not be empty'];
        yield 'bare expression' => [':disabled', 'requires a quoted value'];
        yield 'missing separator' => ['variant="primary"disabled', 'separated by whitespace'];
        yield 'invalid name' => ['BadName="value"', 'Malformed'];
    }
}
