<?php

use Codemonster\Session\Handlers\ArraySessionHandler;
use Codemonster\Session\Session;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetSessionStore();
        unset($_COOKIE['SESSION_ID']);
    }

    private function resetSessionStore(): void
    {
        $reflection = new \ReflectionClass(Session::class);
        $property = $reflection->getProperty('store');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    private function getStoreId(Store $store): string
    {
        $reflection = new \ReflectionClass($store);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);

        return (string) $property->getValue($store);
    }

    public function testSessionStartsAndStoresData(): void
    {
        Session::start('array');
        Session::put('user', 'Vasya');

        $this->assertSame('Vasya', Session::get('user'));
        $this->assertArrayHasKey('user', Session::all());
    }

    public function testForgetRemovesKey(): void
    {
        Session::start('array');
        Session::put('foo', 'bar');
        Session::forget('foo');

        $this->assertNull(Session::get('foo'));
    }

    public function testAllReturnsAllData(): void
    {
        Session::start('array');
        Session::put('a', 1);
        Session::put('b', 2);

        $all = Session::all();

        $this->assertCount(2, $all);
        $this->assertSame(['a' => 1, 'b' => 2], $all);
    }

    public function testDestroyClearsAllData(): void
    {
        Session::start('array');
        Session::put('key', 'value');
        Session::destroy();

        $this->assertSame([], Session::all());
    }

    public function testDestroyDoesNotStartSessionWhenNotStarted(): void
    {
        Session::destroy();

        $reflection = new \ReflectionClass(Session::class);
        $property = $reflection->getProperty('store');
        $property->setAccessible(true);

        $this->assertNull($property->getValue());
    }

    public function testCustomHandlerCanBeUsed(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store($handler);
        $store->start();
        $store->put('x', 10);

        $this->assertSame(10, $store->get('x'));
    }

    public function testRegenerateRotatesIdAndKeepsData(): void
    {
        Session::start('array');
        Session::put('user', 'Vasya');

        $store = Session::store();
        $firstId = $this->getStoreId($store);

        Session::regenerate();

        $storeAfter = Session::store();
        $secondId = $this->getStoreId($storeAfter);

        $this->assertNotSame($firstId, $secondId);
        $this->assertSame('Vasya', Session::get('user'));
    }

    public function testInvalidCookieIdIsRejected(): void
    {
        $_COOKIE['SESSION_ID'] = '../evil';

        $handler = new ArraySessionHandler();
        $store = new Store($handler);

        $id = $this->getStoreId($store);

        $this->assertSame(32, strlen($id));
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{32}\z/', $id);
        $this->assertNotSame($_COOKIE['SESSION_ID'], $id);

        unset($_COOKIE['SESSION_ID']);
    }

    public function testInvalidCustomIdThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $handler = new ArraySessionHandler();
        new Store($handler, '../evil');
    }

    public function testCookieOptionsAutoSecureOnHttps(): void
    {
        $_SERVER['HTTPS'] = 'on';

        try {
            $handler = new ArraySessionHandler();
            $store = new Store($handler);

            $reflection = new \ReflectionClass($store);
            $property = $reflection->getProperty('cookieOptions');
            $property->setAccessible(true);

            $options = $property->getValue($store);

            $this->assertTrue($options['secure']);
        } finally {
            unset($_SERVER['HTTPS']);
        }
    }

    public function testSameSiteNoneForcesSecure(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store($handler, null, ['samesite' => 'None', 'secure' => false]);

        $reflection = new \ReflectionClass($store);
        $property = $reflection->getProperty('cookieOptions');
        $property->setAccessible(true);

        $options = $property->getValue($store);

        $this->assertSame('None', $options['samesite']);
        $this->assertTrue($options['secure']);
    }

    public function testDestroyClearsCookieInProcess(): void
    {
        Session::start('array');
        $_COOKIE['SESSION_ID'] = 'deadbeefdeadbeefdeadbeefdeadbeef';

        Session::destroy();

        $this->assertArrayNotHasKey('SESSION_ID', $_COOKIE);
    }

    public function testHasAndPull(): void
    {
        Session::start('array');
        Session::put('token', 'abc');

        $this->assertTrue(Session::has('token'));
        $this->assertSame('abc', Session::pull('token'));
        $this->assertFalse(Session::has('token'));
    }

    public function testIncrement(): void
    {
        Session::start('array');

        $this->assertSame(1, Session::increment('count'));
        $this->assertSame(3, Session::increment('count', 2));
    }

    public function testForgetMany(): void
    {
        Session::start('array');
        Session::put('a', 1);
        Session::put('b', 2);
        Session::put('c', 3);

        Session::forgetMany(['a', 'c']);

        $this->assertFalse(Session::has('a'));
        $this->assertTrue(Session::has('b'));
        $this->assertFalse(Session::has('c'));
    }

    public function testFlashAgesOutAfterNextStart(): void
    {
        $handler = new ArraySessionHandler();
        $store = new Store($handler);
        $store->start();
        $store->flash('notice', 'ok');

        $store->start();

        $this->assertSame('ok', $store->get('notice'));

        $store->start();

        $this->assertNull($store->get('notice'));
    }

    public function testTtlExpiresKey(): void
    {
        Session::start('array');
        Session::putWithTtl('temp', 'value', 1);

        $this->assertSame('value', Session::get('temp'));

        sleep(2);

        $this->assertNull(Session::get('temp'));
        $this->assertFalse(Session::has('temp'));
    }

    public function testTtlHelpers(): void
    {
        Session::start('array');
        Session::putWithTtl('token', 'abc', 5);

        $ttl = Session::ttl('token');
        $this->assertNotNull($ttl);
        $this->assertGreaterThan(0, $ttl);

        $expiresAt = Session::expiresAt('token');
        $this->assertNotNull($expiresAt);
        $this->assertGreaterThan(time(), $expiresAt);

        $this->assertTrue(Session::touch('token', 10));
        $this->assertGreaterThan($expiresAt, Session::expiresAt('token'));
    }

    public function testScopedSessionsAreIsolated(): void
    {
        Session::start('array');

        $admin = Session::for('admin');
        $user = Session::for('user');

        $admin->put('token', 'admin123');
        $user->put('token', 'user456');

        $this->assertSame('admin123', $admin->get('token'));
        $this->assertSame('user456', $user->get('token'));
        $this->assertSame(['token' => 'admin123'], $admin->all());
        $this->assertSame(['token' => 'user456'], $user->all());
    }

    public function testScopedSessionsSupportCustomDelimiter(): void
    {
        Session::start('array');

        $scope = Session::for('admin', ':');
        $scope->put('token', 'x');

        $this->assertSame('x', $scope->get('token'));
        $keys = Session::keys('admin:');
        $this->assertSame(['admin:token'], $keys);
    }

    public function testSessionManagerProvidesScopedAccess(): void
    {
        Session::start('array');

        $manager = Session::manager();
        $manager->put('token', 'root');

        $scope = $manager->for('admin');
        $scope->put('token', 'admin');

        $this->assertSame('root', $manager->get('token'));
        $this->assertSame('admin', $scope->get('token'));
    }

    public function testKeysAndForgetNamespace(): void
    {
        Session::start('array');
        Session::put('root', 1);

        $admin = Session::for('admin');
        $admin->put('token', 'a');
        $admin->put('id', 42);

        $user = Session::for('user');
        $user->put('token', 'b');

        $keys = Session::keys();
        sort($keys);
        $this->assertSame(['admin.id', 'admin.token', 'root', 'user.token'], $keys);
        $adminKeys = $admin->keys();
        sort($adminKeys);
        $this->assertSame(['id', 'token'], $adminKeys);

        $admin->forgetNamespace();

        $keysAfter = Session::keys();
        sort($keysAfter);
        $this->assertSame(['root', 'user.token'], $keysAfter);
    }

    public function testCountAndTouchAll(): void
    {
        Session::start('array');
        Session::put('root', 1);
        Session::put('a', 1);
        Session::put('b', 2);

        $this->assertSame(3, Session::count());

        $admin = Session::for('admin');
        $admin->putWithTtl('token', 'a', 5);
        $admin->putWithTtl('id', 1, 5);

        $updated = $admin->touchAll(10);
        $this->assertSame(2, $updated);

        $this->assertGreaterThan(0, $admin->ttl('token'));
    }

    public function testDumpAndSize(): void
    {
        Session::start('array');
        Session::put('root', 1);
        Session::put('token', 'abc');

        $dump = Session::dump();
        $this->assertArrayHasKey('root', $dump);
        $this->assertArrayHasKey('token', $dump);

        $size = Session::size();
        $this->assertGreaterThan(0, $size);
    }

    public function testDumpRedactsKeys(): void
    {
        Session::start('array');
        Session::put('token', 'secret');
        Session::put('user', 'bob');

        $dump = Session::dump(['token']);

        $this->assertSame('***', $dump['token']);
        $this->assertSame('bob', $dump['user']);
    }

    public function testKeysMatchAndDumpRedactsPatterns(): void
    {
        Session::start('array');
        Session::put('token', 'secret');
        Session::put('user', 'bob');
        Session::put('user_id', 1);

        $keys = Session::keysMatch('user*');
        sort($keys);
        $this->assertSame(['user', 'user_id'], $keys);

        $dump = Session::dump([], ['user*']);
        $this->assertSame('***', $dump['user']);
        $this->assertSame('***', $dump['user_id']);
        $this->assertSame('secret', $dump['token']);
    }

    public function testEncryptedPayloadRoundTrip(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for encryption.');
        }

        $handler = new ArraySessionHandler();
        $key = random_bytes(32);

        $store = new Store($handler, null, [], ['key' => $key]);
        $store->start();
        $store->put('secret', 'value');

        $id = $this->getStoreId($store);
        $raw = $handler->read($id);

        $this->assertStringStartsWith('v1:', $raw);
        $this->assertStringNotContainsString('value', $raw);

        $store2 = new Store($handler, $id, [], ['key' => $key]);
        $store2->start();

        $this->assertSame('value', $store2->get('secret'));
    }

    public function testEncryptionKeyRotationReencryptsPayload(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for encryption.');
        }

        $handler = new ArraySessionHandler();
        $oldKey = random_bytes(32);

        $store = new Store($handler, null, [], ['key' => $oldKey, 'allow_plaintext' => true]);
        $store->start();
        $store->put('secret', 'value');

        $id = $this->getStoreId($store);
        $store2 = new Store($handler, $id, [], ['key' => $oldKey]);
        $store2->start();

        $newKey = random_bytes(32);
        $store2->rotateEncryptionKey($newKey, [$oldKey]);

        $store3 = new Store($handler, $id, [], ['key' => $newKey]);
        $store3->start();

        $this->assertSame('value', $store3->get('secret'));

        $this->expectException(\RuntimeException::class);

        $store4 = new Store($handler, $id, [], ['key' => $oldKey]);
        $store4->start();
    }

    public function testPlaintextIsMigratedWhenAllowed(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('ext-sodium is required for encryption.');
        }

        $handler = new ArraySessionHandler();
        $id = bin2hex(random_bytes(16));
        $payload = json_encode(['token' => 'abc'], JSON_THROW_ON_ERROR);
        $handler->write($id, $payload);

        $key = random_bytes(32);
        $store = new Store($handler, $id, [], ['key' => $key, 'allow_plaintext' => true]);
        $store->start();

        $raw = $handler->read($id);
        $this->assertStringStartsWith('v1:', $raw);
        $this->assertSame('abc', $store->get('token'));
    }
}
