<?php

namespace Codemonster\Ssr;

use RuntimeException;

class SsrBridge
{
    /**
     * @var array{
     *     transport: string,
     *     mode: string,
     *     script: string,
     *     cwd: string|null,
     *     url: string,
     *     client_path: string,
     *     server_entry: string,
     *     manifest_path: string,
     *     dev_entry_server: string,
     *     client_entry: string,
     *     script_attrs: string,
     *     port: int,
     *     dev_root: string|null,
     *     disable_preload: bool,
     *     disable_js_preload: bool,
     *     disable_css_preload: bool,
     *     disable_font_preload: bool,
     *     disable_image_preload: bool
     * }
     */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'transport' => 'local', // local | http
            'mode' => 'production', // SSR runtime mode: development | production
            'script' => 'node_modules/@codemonster-ru/ssr-service/dist/cli.js',
            'cwd' => getcwd(),
            'url' => 'http://localhost:3000',
            'client_path' => '',
            'server_entry' => '',
            'manifest_path' => '',
            'dev_entry_server' => '',
            'client_entry' => '',
            'script_attrs' => 'type="module"',
            'port' => 3000,
            'dev_root' => null,
            'disable_preload' => false,
            'disable_js_preload' => false,
            'disable_css_preload' => false,
            'disable_font_preload' => false,
            'disable_image_preload' => false,
        ];

        $config = array_merge($defaults, $config);

        $this->config = [
            'transport' => self::stringConfig($config, 'transport'),
            'mode' => self::stringConfig($config, 'mode'),
            'script' => self::stringConfig($config, 'script'),
            'cwd' => self::nullableStringConfig($config, 'cwd'),
            'url' => self::stringConfig($config, 'url'),
            'client_path' => self::stringConfig($config, 'client_path'),
            'server_entry' => self::stringConfig($config, 'server_entry'),
            'manifest_path' => self::stringConfig($config, 'manifest_path'),
            'dev_entry_server' => self::stringConfig($config, 'dev_entry_server'),
            'client_entry' => self::stringConfig($config, 'client_entry'),
            'script_attrs' => self::stringConfig($config, 'script_attrs'),
            'port' => self::intConfig($config, 'port'),
            'dev_root' => self::nullableStringConfig($config, 'dev_root'),
            'disable_preload' => self::boolConfig($config, 'disable_preload'),
            'disable_js_preload' => self::boolConfig($config, 'disable_js_preload'),
            'disable_css_preload' => self::boolConfig($config, 'disable_css_preload'),
            'disable_font_preload' => self::boolConfig($config, 'disable_font_preload'),
            'disable_image_preload' => self::boolConfig($config, 'disable_image_preload'),
        ];
    }

    /**
     * @param array<string, mixed> $props
     */
    public function render(string $component, array $props = []): string
    {
        $payload = json_encode([
            'component' => $component,
            'props' => $props,
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new RuntimeException('Unable to encode SSR payload.');
        }

        return $this->config['transport'] === 'http'
            ? $this->renderHttp($payload)
            : $this->renderLocal($payload);
    }

    protected function renderHttp(string $payload): string
    {
        if (!$this->config['url']) {
            throw new RuntimeException('SSR URL is not configured.');
        }

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 10,
            ],
        ];

        $context = stream_context_create($opts);
        $result = file_get_contents($this->config['url'] . '/render', false, $context);

        if ($result === false) {
            throw new RuntimeException('Remote SSR request failed.');
        }

        return $result;
    }

    protected function renderLocal(string $payload): string
    {
        if (!in_array($this->config['mode'], ['development', 'production'], true)) {
            throw new RuntimeException("Unknown SSR mode: {$this->config['mode']}");
        }

        $script = $this->config['script'];

        if (!file_exists($script)) {
            throw new RuntimeException("SSR script not found: {$script}");
        }

        $args = [$script, 'render'];

        $args[] = '--mode=' . $this->config['mode'];
        $args[] = '--cli-mode=true';
        $args[] = '--client-path=' . $this->config['client_path'];
        $args[] = '--server-entry=' . $this->config['server_entry'];
        $args[] = '--manifest-path=' . $this->config['manifest_path'];
        $args[] = '--dev-entry-server=' . $this->config['dev_entry_server'];
        $args[] = '--client-entry=' . $this->config['client_entry'];
        $args[] = '--script-attrs=' . $this->config['script_attrs'];
        $args[] = '--port=' . $this->config['port'];

        if (!empty($this->config['dev_root'])) {
            $args[] = '--dev-root=' . $this->config['dev_root'];
        }

        foreach ([
            'disable_preload',
            'disable_js_preload',
            'disable_css_preload',
            'disable_font_preload',
            'disable_image_preload',
        ] as $key) {
            if ($this->config[$key]) {
                $args[] = '--' . str_replace('_', '-', $key);
            }
        }

        $result = ProcessHelper::run('node', $args, $payload, $this->config['cwd']);

        if ($result['exitCode'] !== 0) {
            throw new RuntimeException('Local SSR failed: ' . $result['stderr']);
        }

        return $result['stdout'];
    }

    /** @param array<string, mixed> $config */
    private static function stringConfig(array $config, string $key): string
    {
        if (!is_string($config[$key] ?? null)) {
            throw new RuntimeException("SSR config [{$key}] must be a string.");
        }

        return $config[$key];
    }

    /** @param array<string, mixed> $config */
    private static function nullableStringConfig(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        if ($value !== null && !is_string($value)) {
            throw new RuntimeException("SSR config [{$key}] must be a string or null.");
        }

        return $value;
    }

    /** @param array<string, mixed> $config */
    private static function intConfig(array $config, string $key): int
    {
        if (!is_int($config[$key] ?? null)) {
            throw new RuntimeException("SSR config [{$key}] must be an integer.");
        }

        return $config[$key];
    }

    /** @param array<string, mixed> $config */
    private static function boolConfig(array $config, string $key): bool
    {
        if (!is_bool($config[$key] ?? null)) {
            throw new RuntimeException("SSR config [{$key}] must be a boolean.");
        }

        return $config[$key];
    }
}
