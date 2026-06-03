<?php

use Codemonster\Session\Handlers\CacheSessionHandler;
use Codemonster\Session\Store;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

class CacheSessionHandlerTest extends TestCase
{
    public function testReadWriteAndDestroy(): void
    {
        $cache = new ArrayCache();
        $handler = new CacheSessionHandler($cache);
        $id = bin2hex(random_bytes(16));

        $store = new Store($handler, $id);
        $store->start();
        $store->put('token', '123');

        $store2 = new Store($handler, $id);
        $store2->start();

        $this->assertSame('123', $store2->get('token'));

        $handler->destroy($id);

        $this->assertSame('', $handler->read($id));
    }

    public function testRetryOnFailure(): void
    {
        $cache = new FlakyCache();

        $handler = new CacheSessionHandler($cache, 'sess_', 0, 1, 0);
        $this->assertTrue($handler->write('abc', 'value'));
    }
}

/**
 * @psalm-suppress MethodSignatureMismatch
 */
trait ArrayCacheStorage
{
    /** @var array<string, mixed> */
    private array $data = [];

    private function getValue(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    private function setValue(string $key, mixed $value): bool
    {
        $this->data[$key] = $value;

        return true;
    }

    private function deleteValue(string $key): bool
    {
        unset($this->data[$key]);

        return true;
    }

    private function clearValues(): bool
    {
        $this->data = [];

        return true;
    }

    private function getValues(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->data[$key] ?? $default;
        }

        return $result;
    }

    private function setValues(iterable $values): bool
    {
        foreach ($values as $key => $value) {
            $this->data[$key] = $value;
        }

        return true;
    }

    private function deleteValues(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->data[$key]);
        }

        return true;
    }

    private function hasValue(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}

$method = new ReflectionMethod(CacheInterface::class, 'get');
$typedSignatures = $method->hasReturnType() || $method->getParameters()[0]->hasType();

if ($typedSignatures) {
    class ArrayCache implements CacheInterface
    {
        use ArrayCacheStorage;

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->getValue($key, $default);
        }

        public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
        {
            return $this->setValue($key, $value);
        }

        public function delete(string $key): bool
        {
            return $this->deleteValue($key);
        }

        public function clear(): bool
        {
            return $this->clearValues();
        }

        public function getMultiple(iterable $keys, mixed $default = null): iterable
        {
            return $this->getValues($keys, $default);
        }

        public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
        {
            return $this->setValues($values);
        }

        public function deleteMultiple(iterable $keys): bool
        {
            return $this->deleteValues($keys);
        }

        public function has(string $key): bool
        {
            return $this->hasValue($key);
        }
    }

    class FlakyCache extends ArrayCache
    {
        private int $tries = 0;

        public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
        {
            $this->tries++;
            if ($this->tries === 1) {
                throw new \RuntimeException('fail');
            }

            return parent::set($key, $value, $ttl);
        }
    }
} else {
    class ArrayCache implements CacheInterface
    {
        use ArrayCacheStorage;

        public function get($key, $default = null)
        {
            return $this->getValue((string) $key, $default);
        }

        public function set($key, $value, $ttl = null)
        {
            return $this->setValue((string) $key, $value);
        }

        public function delete($key)
        {
            return $this->deleteValue((string) $key);
        }

        public function clear()
        {
            return $this->clearValues();
        }

        public function getMultiple($keys, $default = null)
        {
            return $this->getValues($keys, $default);
        }

        public function setMultiple($values, $ttl = null)
        {
            return $this->setValues($values);
        }

        public function deleteMultiple($keys)
        {
            return $this->deleteValues($keys);
        }

        public function has($key)
        {
            return $this->hasValue((string) $key);
        }
    }

    class FlakyCache extends ArrayCache
    {
        private int $tries = 0;

        public function set($key, $value, $ttl = null)
        {
            $this->tries++;
            if ($this->tries === 1) {
                throw new \RuntimeException('fail');
            }

            return parent::set($key, $value, $ttl);
        }
    }
}
