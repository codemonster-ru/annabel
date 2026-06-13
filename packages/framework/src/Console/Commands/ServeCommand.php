<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;

class ServeCommand extends Command
{
    public function getName(): string
    {
        return 'serve';
    }

    public function getDescription(): string
    {
        return 'Run the PHP built-in server (default: 127.0.0.1:8000).';
    }

    public function getUsage(): string
    {
        return 'serve [host:port|port]';
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();
        $app = $console->getApplication();

        [$host, $port] = $this->parseAddress($arguments[0] ?? null);

        $publicDir = $app->getBasePath() . DIRECTORY_SEPARATOR . 'public';
        $index = $publicDir . DIRECTORY_SEPARATOR . 'index.php';

        if (!is_file($index)) {
            $console->writeln($console->color("public/index.php not found in {$publicDir}", 'error'));

            return 1;
        }

        $console->writeln($console->color("Starting server at http://{$host}:{$port}", 'label'));
        $console->writeln($console->color('Press Ctrl+C to stop', 'muted'));

        $router = $this->createRouterScript($publicDir, $index);

        $command = sprintf(
            'php -S %s:%s -t %s %s',
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($publicDir),
            escapeshellarg($router),
        );

        try {
            passthru($command, $exitCode);
        } finally {
            if (is_file($router)) {
                @unlink($router);
            }
        }

        return (int) $exitCode;
    }

    /**
     * @return array{string,int}
     */
    protected function parseAddress(?string $arg): array
    {
        $host = '127.0.0.1';
        $port = 8000;

        if (!$arg) {
            return [$host, $port];
        }

        if (str_contains($arg, ':')) {
            [$h, $p] = explode(':', $arg, 2);
            $host = $h ?: $host;
            $port = (int) $p ?: $port;
        } elseif (is_numeric($arg)) {
            $port = (int) $arg;
        }

        return [$host, $port];
    }

    protected function createRouterScript(string $publicDir, string $index): string
    {
        $router = tempnam(sys_get_temp_dir(), 'annabel-serve-');

        if ($router === false) {
            throw new \RuntimeException('Unable to create temporary server router.');
        }

        $publicRoot = realpath($publicDir);

        if ($publicRoot === false) {
            throw new \RuntimeException("Public directory not found: {$publicDir}");
        }

        file_put_contents($router, $this->routerScript($publicRoot, $index));

        return $router;
    }

    protected function routerScript(string $publicRoot, string $index): string
    {
        $publicRoot = var_export($publicRoot, true);
        $index = var_export($index, true);

        return <<<PHP
<?php

\$publicRoot = {$publicRoot};
\$index = {$index};
\$path = parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string(\$path)) {
    \$file = realpath(\$publicRoot . '/' . ltrim(rawurldecode(\$path), '/'));

    if (is_string(\$file) && str_starts_with(\$file, \$publicRoot . DIRECTORY_SEPARATOR) && is_file(\$file)) {
        return false;
    }
}

return require \$index;
PHP
            . PHP_EOL;
    }
}
