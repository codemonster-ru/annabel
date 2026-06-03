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

        $command = sprintf(
            'php -S %s:%s -t %s %s',
            escapeshellarg($host),
            escapeshellarg((string)$port),
            escapeshellarg($publicDir),
            escapeshellarg($index)
        );

        passthru($command, $exitCode);

        return (int)$exitCode;
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
            $port = (int)$p ?: $port;
        } elseif (is_numeric($arg)) {
            $port = (int)$arg;
        }

        return [$host, $port];
    }
}
