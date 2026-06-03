<?php

namespace Codemonster\Ssr;

use RuntimeException;

class SsrBridge
{
    protected array $config;

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

        $this->config = array_merge($defaults, $config);
    }

    public function render(string $component, array $props = []): string
    {
        $payload = json_encode([
            'component' => $component,
            'props' => $props,
        ], JSON_UNESCAPED_UNICODE);

        return $this->config['transport'] === 'http'
            ? $this->renderHttp($payload)
            : $this->renderLocal($payload);
    }

    protected function renderHttp(string $payload): string
    {
        if (!$this->config['url']) {
            throw new RuntimeException("SSR URL is not configured.");
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
            'disable-preload',
            'disable-js-preload',
            'disable-css-preload',
            'disable-font-preload',
            'disable-image-preload',
        ] as $flag) {
            if (!empty($this->config[$flag])) {
                $args[] = '--' . $flag;
            }
        }

        $cwd = $this->config['cwd'] ?? getcwd();

        $result = ProcessHelper::run('node', $args, $payload, $cwd);

        if ($result['exitCode'] !== 0) {
            throw new RuntimeException("Local SSR failed: " . $result['stderr']);
        }

        return $result['stdout'];
    }
}
