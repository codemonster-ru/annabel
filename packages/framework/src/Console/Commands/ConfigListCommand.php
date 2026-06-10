<?php

namespace Codemonster\Annabel\Console\Commands;

use Codemonster\Annabel\Console\Command;
use Codemonster\Annabel\Console\Contracts\InputInterface;
use Codemonster\Annabel\Console\Contracts\OutputInterface;
use Codemonster\Annabel\Console\ExitCode;
use Codemonster\Config\Config;

class ConfigListCommand extends Command
{
    /** @var list<string> */
    private const SENSITIVE_SEGMENTS = [
        'api_key',
        'client_secret',
        'credential_key',
        'key',
        'pass',
        'password',
        'private_key',
        'secret',
        'token',
    ];

    public function getName(): string
    {
        return 'config:list';
    }

    public function getAliases(): array
    {
        return ['configs'];
    }

    public function getDescription(): string
    {
        return 'Show configuration values with secrets redacted.';
    }

    public function getUsage(): string
    {
        return 'config:list [--show-secrets]';
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $showSecrets = $input->hasOption('show-secrets');

        /** @var Config $config */
        $config = $this->console()->getApplication()->make(Config::class);
        $items = $this->flatten($config->all());

        if ($items === []) {
            $output->writeln('No configuration loaded.');

            return ExitCode::SUCCESS;
        }

        ksort($items);

        $output->writeln('Configuration:');

        foreach ($items as $key => $value) {
            $output->writeln(sprintf(
                '  %s = %s',
                $key,
                $this->formatValue(!$showSecrets && $this->isSensitiveKey($key) ? '********' : $value),
            ));
        }

        return ExitCode::SUCCESS;
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    protected function flatten(array $items, string $prefix = ''): array
    {
        $flat = [];

        foreach ($items as $key => $value) {
            $key = (string) $key;
            $path = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value) && $value !== []) {
                /** @var array<string, mixed> $value */
                $flat += $this->flatten($value, $path);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }

    protected function isSensitiveKey(string $key): bool
    {
        $segments = array_map('strtolower', explode('.', $key));

        foreach ($segments as $segment) {
            if (in_array($segment, self::SENSITIVE_SEGMENTS, true)) {
                return true;
            }
        }

        return false;
    }

    protected function formatValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_SLASHES);

        return $json === false ? get_debug_type($value) : $json;
    }
}
