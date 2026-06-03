<?php

namespace Codemonster\Dumper\Tests;

use Codemonster\Dumper\Dumper;
use PHPUnit\Framework\TestCase;

class DumperTest extends TestCase
{
    public function testDumpCliOutput(): void
    {
        ob_start();

        Dumper::dump(['foo' => 'bar'], 'cli');

        $output = ob_get_clean();

        $this->assertStringContainsString('foo', $output);
        $this->assertStringContainsString('bar', $output);
        $this->assertStringContainsString("\033[33m", $output);
    }

    public function testDumpHtmlOutput(): void
    {
        ob_start();

        Dumper::dump(['baz' => 'qux'], 'html');

        $output = ob_get_clean();

        $this->assertStringContainsString('<pre', $output);
        $this->assertStringContainsString('baz', $output);
    }

    public function testInvalidModeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Dumper::dump('test', 'invalid');
    }

    public function testDdOutputsAndStopsExecution(): void
    {
        $called = false;

        $mock = new class extends Dumper {
            protected static function terminate(): never
            {
                throw new \RuntimeException('terminate called');
            }
        };

        ob_start();

        try {
            $mock::dd(['foo' => 'bar'], 'cli');
        } catch (\RuntimeException $e) {
            $called = $e->getMessage() === 'terminate called';
        }

        $output = ob_get_clean();

        $this->assertTrue($called, 'terminate() should be called');
        $this->assertStringContainsString('foo', $output);
    }
}
