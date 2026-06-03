<?php

namespace Codemonster\Session;

use SessionHandlerInterface;

class Store
{
    protected const COOKIE_NAME = 'SESSION_ID';
    protected const ID_PATTERN = '/\A[a-f0-9]{32}\z/';
    protected const FLASH_NEW = '__flash_new';
    protected const FLASH_OLD = '__flash_old';
    protected const ENCRYPTION_PREFIX = 'v1:';
    protected const TTL_KEY = '__ttl';

    protected string $id;
    /** @var array<string, mixed> */
    protected array $data = [];
    protected SessionHandlerInterface $handler;
    /** @var array<string, mixed> */
    protected array $cookieOptions = [];
    protected ?int $cookieLifetime = null;
    protected ?int $cookieExpires = null;
    protected ?string $encryptionKey = null;
    /** @var array<int, string> */
    protected array $encryptionKeys = [];
    protected bool $allowPlaintext = false;

    /**
     * @param array<string, mixed> $cookieOptions
     * @param array<string, mixed> $encryptionOptions
     */
    public function __construct(
        SessionHandlerInterface $handler,
        ?string $id = null,
        array $cookieOptions = [],
        array $encryptionOptions = []
    )
    {
        $existing = $_COOKIE[self::COOKIE_NAME] ?? null;

        if ($id !== null) {
            if (!self::isValidId($id)) {
                throw new \InvalidArgumentException('Invalid session id.');
            }

            $this->id = $id;
        } else {
            $this->id = self::isValidId($existing) ? $existing : bin2hex(random_bytes(16));
        }
        $this->handler = $handler;
        $this->cookieOptions = $this->normalizeCookieOptions($cookieOptions);
        $this->normalizeEncryptionOptions($encryptionOptions);
    }

    protected static function isValidId(?string $id): bool
    {
        return is_string($id) && preg_match(self::ID_PATTERN, $id) === 1;
    }

    protected function setSessionCookie(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $options = $this->cookieOptions;
        $options['expires'] = $this->cookieExpires ?? (time() + (int) $this->cookieLifetime);

        setcookie(self::COOKIE_NAME, $this->id, $options);
    }

    protected function clearSessionCookie(): void
    {
        unset($_COOKIE[self::COOKIE_NAME]);

        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $options = $this->cookieOptions;
        $options['expires'] = time() - 3600;

        setcookie(self::COOKIE_NAME, '', $options);
    }

    public function start(): void
    {
        if (!isset($_COOKIE[self::COOKIE_NAME]) || $_COOKIE[self::COOKIE_NAME] !== $this->id) {
            $this->setSessionCookie();
        }

        $raw = $this->handler->read($this->id);

        if (!is_string($raw) || $raw === '') {
            $this->data = [];
            return;
        }

        try {
            $payload = $this->decryptPayload($raw);
            $this->data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Invalid session payload.', 0, $e);
        }

        $this->maybeMigratePlaintext($raw);

        $this->ageFlashData();
    }

    public function regenerateId(bool $destroyOld = true): void
    {
        $oldId = $this->id;
        $this->id = bin2hex(random_bytes(16));

        $this->setSessionCookie();
        $this->persist();

        if ($destroyOld) {
            $this->handler->destroy($oldId);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
            return $default;
        }

        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
            return false;
        }

        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<int, string>
     */
    public function keys(?string $prefix = null): array
    {
        $this->sweepExpired();

        $keys = [];
        foreach ($this->data as $key => $_value) {
            if (!is_string($key) || str_starts_with($key, '__')) {
                continue;
            }
            if ($prefix !== null && !str_starts_with($key, $prefix)) {
                continue;
            }
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @return array<int, string>
     */
    public function keysMatch(string $pattern): array
    {
        $this->sweepExpired();

        $keys = [];
        foreach ($this->data as $key => $_value) {
            if (!is_string($key) || str_starts_with($key, '__')) {
                continue;
            }
            if (!$this->matchesPattern($key, $pattern)) {
                continue;
            }
            $keys[] = $key;
        }

        return $keys;
    }

    public function count(): int
    {
        $this->sweepExpired();

        $count = 0;
        foreach ($this->data as $key => $_value) {
            if (!is_string($key) || str_starts_with($key, '__')) {
                continue;
            }
            $count++;
        }

        return $count;
    }

    public function size(): int
    {
        $this->sweepExpired();

        try {
            $payload = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode session payload.', 0, $e);
        }

        $payload = $this->encryptPayload($payload);

        return strlen($payload);
    }

    /**
     * @param array<int, string> $redactKeys
     * @param array<int, string> $redactPatterns
     * @return array<string, mixed>
     */
    public function dump(array $redactKeys = [], array $redactPatterns = []): array
    {
        $this->sweepExpired();
        $data = $this->data;

        unset($data[self::TTL_KEY], $data[self::FLASH_NEW], $data[self::FLASH_OLD]);

        if ($redactKeys !== []) {
            $lookup = array_fill_keys($redactKeys, true);
            foreach ($data as $key => $_value) {
                if (isset($lookup[$key])) {
                    $data[$key] = '***';
                }
            }
        }

        if ($redactPatterns !== []) {
            foreach ($data as $key => $_value) {
                foreach ($redactPatterns as $pattern) {
                    if ($this->matchesPattern($key, $pattern)) {
                        $data[$key] = '***';
                        break;
                    }
                }
            }
        }

        return $data;
    }

    public function put(string $key, mixed $value): void
    {
        $this->data[$key] = $value;

        $ttl = $this->getTtlMap();
        if (array_key_exists($key, $ttl)) {
            unset($ttl[$key]);
            $this->setTtlMap($ttl);
        }

        $this->persist();
    }

    public function putWithTtl(string $key, mixed $value, int $ttlSeconds): void
    {
        if ($ttlSeconds <= 0) {
            $this->forget($key);
            return;
        }

        $this->data[$key] = $value;

        $ttl = $this->getTtlMap();
        $ttl[$key] = time() + $ttlSeconds;
        $this->setTtlMap($ttl);

        $this->persist();
    }

    public function ttl(string $key): ?int
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
            return null;
        }

        $ttl = $this->getTtlMap();
        if (!array_key_exists($key, $ttl)) {
            return null;
        }

        $remaining = (int) $ttl[$key] - time();

        return $remaining > 0 ? $remaining : 0;
    }

    public function expiresAt(string $key): ?int
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
            return null;
        }

        $ttl = $this->getTtlMap();
        if (!array_key_exists($key, $ttl)) {
            return null;
        }

        $expiresAt = (int) $ttl[$key];

        return $expiresAt > time() ? $expiresAt : null;
    }

    public function touch(string $key, int $ttlSeconds): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        if ($ttlSeconds <= 0) {
            $this->forget($key);
            return false;
        }

        $ttl = $this->getTtlMap();
        $ttl[$key] = time() + $ttlSeconds;
        $this->setTtlMap($ttl);
        $this->persist();

        return true;
    }

    public function touchAll(int $ttlSeconds, ?string $prefix = null): int
    {
        $this->sweepExpired();

        if ($ttlSeconds <= 0) {
            return 0;
        }

        $now = time();
        $ttl = $this->getTtlMap();
        $updated = 0;

        foreach ($this->data as $key => $_value) {
            if (!is_string($key) || str_starts_with($key, '__')) {
                continue;
            }
            if ($prefix !== null && !str_starts_with($key, $prefix)) {
                continue;
            }

            $ttl[$key] = $now + $ttlSeconds;
            $updated++;
        }

        if ($updated > 0) {
            $this->setTtlMap($ttl);
            $this->persist();
        }

        return $updated;
    }

    public function forget(string $key): void
    {
        unset($this->data[$key]);

        $ttl = $this->getTtlMap();
        if (array_key_exists($key, $ttl)) {
            unset($ttl[$key]);
            $this->setTtlMap($ttl);
        }

        $this->persist();
    }

    /**
     * @param array<int, string> $keys
     */
    public function forgetMany(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $ttl = $this->getTtlMap();
        foreach ($keys as $key) {
            unset($this->data[$key]);
            if (array_key_exists($key, $ttl)) {
                unset($ttl[$key]);
            }
        }
        $this->setTtlMap($ttl);

        $this->persist();
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
            return $default;
        }

        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function increment(string $key, int $by = 1): int
    {
        if ($this->purgeExpiredKey($key)) {
            $this->persist();
        }

        $current = $this->data[$key] ?? 0;
        $value = (int) $current + $by;

        $this->data[$key] = $value;
        $this->persist();

        return $value;
    }

    public function flash(string $key, mixed $value): void
    {
        $this->data[$key] = $value;

        $flash = $this->data[self::FLASH_NEW] ?? [];
        if (!is_array($flash)) {
            $flash = [];
        }

        if (!in_array($key, $flash, true)) {
            $flash[] = $key;
        }

        $this->data[self::FLASH_NEW] = $flash;

        $this->persist();
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->sweepExpired();
        return $this->data;
    }

    public function destroy(bool $clearCookie = true): void
    {
        $this->data = [];
        $this->handler->destroy($this->id);

        if ($clearCookie) {
            $this->clearSessionCookie();
        }
    }

    public function forgetNamespace(string $prefix): void
    {
        $this->sweepExpired();

        $ttl = $this->getTtlMap();
        $changed = false;
        $prefix = rtrim($prefix, '.') . '.';

        foreach ($this->data as $key => $_value) {
            if (!is_string($key) || !str_starts_with($key, $prefix)) {
                continue;
            }
            unset($this->data[$key]);
            if (array_key_exists($key, $ttl)) {
                unset($ttl[$key]);
            }
            $changed = true;
        }

        if ($changed) {
            $this->setTtlMap($ttl);
            $this->persist();
        }
    }

    protected function persist(): void
    {
        try {
            $payload = json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode session payload.', 0, $e);
        }

        $payload = $this->encryptPayload($payload);

        $this->handler->write(
            $this->id,
            $payload
        );
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function normalizeCookieOptions(array $options): array
    {
        $this->cookieLifetime = array_key_exists('lifetime', $options) ? (int) $options['lifetime'] : 86400 * 30;
        unset($options['lifetime']);

        if (array_key_exists('expires', $options)) {
            $this->cookieExpires = $options['expires'] !== null ? (int) $options['expires'] : null;
            unset($options['expires']);
        }

        $defaults = [
            'path' => '/',
            'secure' => self::isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax'
        ];

        $merged = array_merge($defaults, $options);

        if (strcasecmp((string) $merged['samesite'], 'none') === 0 && $merged['secure'] !== true) {
            $merged['secure'] = true;
        }

        if (array_key_exists('domain', $merged) && $merged['domain'] === null) {
            unset($merged['domain']);
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function normalizeEncryptionOptions(array $options): void
    {
        if ($options === []) {
            return;
        }

        if (!function_exists('sodium_crypto_secretbox')) {
            throw new \RuntimeException('ext-sodium is required for encryption.');
        }

        $key = $options['key'] ?? null;
        if (!is_string($key) || $key === '') {
            throw new \InvalidArgumentException('Encryption key is required.');
        }

        $primaryKey = $this->normalizeEncryptionKey($key);

        $previous = $options['previous_keys'] ?? [];
        if (!is_array($previous)) {
            throw new \InvalidArgumentException('previous_keys must be an array.');
        }

        $keys = [$primaryKey];
        foreach ($previous as $prev) {
            if (!is_string($prev) || $prev === '') {
                throw new \InvalidArgumentException('Each previous key must be a non-empty string.');
            }
            $keys[] = $this->normalizeEncryptionKey($prev);
        }

        $this->allowPlaintext = (bool) ($options['allow_plaintext'] ?? false);
        $this->encryptionKey = $primaryKey;
        $this->encryptionKeys = $keys;
    }

    /**
     * @param array<int, string> $previousKeys
     */
    public function rotateEncryptionKey(string $newKey, array $previousKeys = [], bool $allowPlaintext = false): void
    {
        $this->normalizeEncryptionOptions([
            'key' => $newKey,
            'previous_keys' => $previousKeys,
            'allow_plaintext' => $allowPlaintext
        ]);

        $this->persist();
    }

    protected function normalizeEncryptionKey(string $key): string
    {
        $raw = null;

        if (strlen($key) === 64 && ctype_xdigit($key)) {
            $raw = hex2bin($key);
        } else {
            $decoded = base64_decode($key, true);
            if ($decoded !== false) {
                $raw = $decoded;
            }
        }

        if ($raw === null) {
            $raw = $key;
        }

        if (strlen($raw) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException('Encryption key must be 32 bytes (raw), 64 hex chars, or base64.');
        }

        return $raw;
    }

    protected function encryptPayload(string $payload): string
    {
        if ($this->encryptionKey === null) {
            return $payload;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($payload, $nonce, $this->encryptionKey);
        $encoded = base64_encode($nonce . $cipher);

        return self::ENCRYPTION_PREFIX . $encoded;
    }

    protected function decryptPayload(string $payload): string
    {
        if ($this->encryptionKey === null) {
            return $payload;
        }

        if (!str_starts_with($payload, self::ENCRYPTION_PREFIX)) {
            if ($this->allowPlaintext) {
                return $payload;
            }

            throw new \RuntimeException('Encrypted session payload expected.');
        }

        $encoded = substr($payload, strlen(self::ENCRYPTION_PREFIX));
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid encrypted payload encoding.');
        }

        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (strlen($decoded) <= $nonceSize) {
            throw new \RuntimeException('Invalid encrypted payload.');
        }

        $nonce = substr($decoded, 0, $nonceSize);
        $cipher = substr($decoded, $nonceSize);

        foreach ($this->encryptionKeys as $key) {
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            if ($plain !== false) {
                return $plain;
            }
        }

        throw new \RuntimeException('Failed to decrypt session payload.');
    }

    protected function maybeMigratePlaintext(string $rawPayload): void
    {
        if ($this->encryptionKey === null || !$this->allowPlaintext) {
            return;
        }

        if (str_starts_with($rawPayload, self::ENCRYPTION_PREFIX)) {
            return;
        }

        $this->persist();
    }

    protected static function isHttpsRequest(): bool
    {
        $https = $_SERVER['HTTPS'] ?? null;

        if (is_string($https) && $https !== '' && strtolower($https) !== 'off') {
            return true;
        }

        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        if (is_string($forwarded) && $forwarded !== '') {
            $proto = strtolower(trim(explode(',', $forwarded)[0]));
            return $proto === 'https';
        }

        return false;
    }

    protected function ageFlashData(): void
    {
        $changed = false;
        $flashOld = $this->data[self::FLASH_OLD] ?? [];

        if (is_array($flashOld)) {
            foreach ($flashOld as $key) {
                if (array_key_exists($key, $this->data)) {
                    unset($this->data[$key]);
                    $changed = true;
                }
            }
        }

        $flashNew = $this->data[self::FLASH_NEW] ?? [];
        if (!is_array($flashNew)) {
            $flashNew = [];
        }

        if (($this->data[self::FLASH_OLD] ?? null) !== $flashNew) {
            $this->data[self::FLASH_OLD] = $flashNew;
            $changed = true;
        }

        if (($this->data[self::FLASH_NEW] ?? null) !== []) {
            $this->data[self::FLASH_NEW] = [];
            $changed = true;
        }

        if ($changed) {
            $this->persist();
        }
    }

    /**
     * @return array<string, int>
     */
    protected function getTtlMap(): array
    {
        $ttl = $this->data[self::TTL_KEY] ?? [];

        return is_array($ttl) ? $ttl : [];
    }

    /**
     * @param array<string, int> $ttl
     */
    protected function setTtlMap(array $ttl): void
    {
        if ($ttl === []) {
            unset($this->data[self::TTL_KEY]);
            return;
        }

        $this->data[self::TTL_KEY] = $ttl;
    }

    protected function purgeExpiredKey(string $key): bool
    {
        $ttl = $this->getTtlMap();
        if (!array_key_exists($key, $ttl)) {
            return false;
        }

        if ((int) $ttl[$key] > time()) {
            return false;
        }

        unset($ttl[$key], $this->data[$key]);
        $this->setTtlMap($ttl);

        return true;
    }

    public function sweepExpired(): void
    {
        $ttl = $this->getTtlMap();
        if ($ttl === []) {
            return;
        }

        $now = time();
        $changed = false;

        foreach ($ttl as $key => $expiresAt) {
            if ((int) $expiresAt <= $now) {
                unset($ttl[$key], $this->data[$key]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->setTtlMap($ttl);
            $this->persist();
        }
    }

    private function matchesPattern(string $key, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (strpbrk($pattern, '*?[]') === false) {
            return $key === $pattern;
        }

        if (function_exists('fnmatch')) {
            return fnmatch($pattern, $key);
        }

        $escaped = preg_quote($pattern, '/');
        $regex = '/^' . str_replace(['\*', '\?'], ['.*', '.'], $escaped) . '$/u';

        return preg_match($regex, $key) === 1;
    }
}
