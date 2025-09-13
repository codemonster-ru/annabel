<?php

namespace Annabel\Tests\Helpers;

use PHPUnit\Framework\TestCase;
use Annabel\Tests\Utils\ProcessHelper;

class DebugTest extends TestCase
{
    public function testDdOutputsAndDies()
    {
        $result = ProcessHelper::runPhpCode('dd(["foo" => "bar"]);');

        $this->assertStringContainsString('foo', $result['stdout']);
        $this->assertStringContainsString('bar', $result['stdout']);
        $this->assertSame(1, $result['exitCode'], 'Expected exit code 1 from dd()');
        $this->assertEmpty(trim($result['stderr']), 'stderr should not contain any errors');
    }
}
