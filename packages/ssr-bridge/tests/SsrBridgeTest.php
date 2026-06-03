<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Codemonster\Ssr\SsrBridge;

#[CoversClass(SsrBridge::class)]
final class SsrBridgeTest extends TestCase
{
    #[Test]
    public function unknownModeThrows(): void
    {
        try {
            $bridge = new SsrBridge([
                'mode' => 'foo',
                'script' => __FILE__,
            ]);
            $bridge->render('Home');

            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Unknown SSR mode: foo', $e->getMessage());
        }
    }
}
