<?php

declare(strict_types=1);

namespace Codemonster\Razor\Tests\Components\Parsing;

use Codemonster\Razor\Components\ComponentRegistry;
use Codemonster\Razor\Components\Parsing\ComponentParser;
use Codemonster\Razor\Components\Parsing\ComponentSlotParser;
use Codemonster\Razor\Exceptions\RazorException;
use PHPUnit\Framework\TestCase;

final class ComponentSlotParserTest extends TestCase
{
    private ComponentSlotParser $parser;

    protected function setUp(): void
    {
        $registry = new ComponentRegistry();
        $registry->registerPrefix('cm', []);
        $this->parser = new ComponentSlotParser(new ComponentParser($registry));
    }

    public function testExtractsNamedSlotsAndPreservesDefaultContent(): void
    {
        $slots = $this->parser->parse(
            'Before<razor-slot name="leading">Lead</razor-slot>After<razor-slot name="trailing">Trail</razor-slot>',
        );

        self::assertSame('BeforeAfter', $slots->default);
        self::assertSame(['leading' => 'Lead', 'trailing' => 'Trail'], $slots->named);
    }

    public function testLeavesSlotsInsideNestedComponentsForTheirOwnCompilation(): void
    {
        $content = '<cm-card><razor-slot name="header">Header</razor-slot>Body</cm-card>';

        self::assertSame($content, $this->parser->parse($content)->default);
    }

    public function testRejectsDuplicateNamedSlots(): void
    {
        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('Duplicate');

        $this->parser->parse(
            '<razor-slot name="header">One</razor-slot><razor-slot name="header">Two</razor-slot>',
        );
    }

    public function testRejectsNamedSlotsNestedInsideOrdinaryMarkup(): void
    {
        $this->expectException(RazorException::class);
        $this->expectExceptionMessage('direct component children');

        $this->parser->parse('<div><razor-slot name="header">Header</razor-slot></div>');
    }
}
