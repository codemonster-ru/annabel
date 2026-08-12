<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components\Parsing;

use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use PHPUnit\Framework\TestCase;

final class ComponentParserTest extends TestCase
{
    private ComponentParser $parser;

    protected function setUp(): void
    {
        $registry = new ComponentRegistry();
        $registry->registerPrefix('cm', []);
        $this->parser = new ComponentParser($registry);
    }

    public function testParsesSelfClosingComponentAtAnExactOffset(): void
    {
        $source = 'before <cm-button variant="ghost" :loading="$saving" disabled /> after';
        $start = strpos($source, '<');
        self::assertIsInt($start);

        $invocation = $this->parser->parseAt($source, $start);

        self::assertNotNull($invocation);
        self::assertSame('cm-button', $invocation->tag);
        self::assertSame('variant="ghost" :loading="$saving" disabled', $invocation->attributes);
        self::assertTrue($invocation->isSelfClosing());
        self::assertSame($start, $invocation->startOffset);
        self::assertSame(' after', substr($source, $invocation->endOffset));
    }

    public function testIgnoresClosingSyntaxInsideQuotedAttributes(): void
    {
        $source = '<cm-card title="literal /> marker"/>';

        $invocation = $this->parser->parseAt($source, 0);

        self::assertNotNull($invocation);
        self::assertSame('title="literal /> marker"', $invocation->attributes);
        self::assertSame(strlen($source), $invocation->endOffset);
    }

    public function testParsesUnknownNamesOwnedByARegisteredPrefix(): void
    {
        self::assertSame('cm-unknown', $this->parser->parseAt('<cm-unknown/>', 0)?->tag);
    }

    public function testLeavesNativeAndUnregisteredTagsUntouched(): void
    {
        self::assertNull($this->parser->parseAt('<button/>', 0));
        self::assertNull($this->parser->parseAt('<other-card/>', 0));
    }

    public function testDoesNotYetConsumePairedComponents(): void
    {
        self::assertNull($this->parser->parseAt('<cm-button>Save</cm-button>', 0));
    }
}
