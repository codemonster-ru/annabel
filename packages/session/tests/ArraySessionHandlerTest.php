<?php

use Codemonster\Session\Handlers\ArraySessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class ArraySessionHandlerTest extends TestCase
{
    public function testReadWriteAndDestroy(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store($handler);
        $store->start();

        $store->put('token', '123');
        $this->assertSame('123', $store->get('token'));

        $store->forget('token');
        $this->assertNull($store->get('token'));
    }

    public function testHandlerStoresRawData(): void
    {
        $handler = new ArraySessionHandler();
        $payload = '{"x":1,"y":2}';
        $handler->write('abc', $payload);
        $stored = $handler->read('abc');

        $this->assertSame($payload, $stored);
    }
}
