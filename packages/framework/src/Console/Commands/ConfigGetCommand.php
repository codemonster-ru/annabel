<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Config\Config;

class ConfigGetCommand extends Command
{
    public function getName(): string
    {
        return 'config:get';
    }

    public function getDescription(): string
    {
        return 'Get a configuration value by key (dot notation).';
    }

    public function getUsage(): string
    {
        return 'config:get key';
    }

    public function handle(array $arguments = []): int
    {
        $console = $this->console();

        if (empty($arguments[0])) {
            $console->writeln($console->color('Usage: php vendor/bin/annabel config:get key', 'error'));

            return 1;
        }

        $key = $arguments[0];
        $app = $console->getApplication();

        $config = $app->make(Config::class);
        $value = $config->get($key);

        if ($value === null) {
            $console->writeln($console->color("null (or key not found): {$key}", 'muted'));

            return 0;
        }

        $console->writeln($console->color($key, 'label') . ':');
        $console->writeln($this->formatValue($value));

        return 0;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return '  ' . var_export($value, true);
        }

        $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json !== false) {
            $lines = explode("\n", $json);

            return '  ' . implode("\n  ", $lines);
        }

        return '  ' . print_r($value, true);
    }
}
