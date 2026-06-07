<?php

namespace Codemonster\Support\Tests\Helpers;

use PHPUnit\Framework\TestCase;

class DumpHelperTest extends TestCase
{
    public function testDumpReturnsValue()
    {
        ob_start();
        $value = dump('Codemonster');
        ob_end_clean();

        $this->assertSame('Codemonster', $value);
    }
}
