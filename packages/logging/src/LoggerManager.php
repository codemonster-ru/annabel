<?php

namespace Codemonster\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerManager
{
    /** @var array<string, mixed> */
    protected array $config;
    /** @var array<string, LoggerInterface> */
    protected array $channels = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function defaultChannel(): string
    {
        $default = $this->config['default'] ?? 'null';

        return is_string($default) && $default !== '' ? $default : 'null';
    }

    public function channel(?string $name = null): LoggerInterface
    {
        $name ??= $this->defaultChannel();

        if ($name === '') {
            throw new LoggingException('Logging channel name cannot be empty.');
        }

        return $this->channels[$name] ??= $this->createChannel($name);
    }

    public function setChannel(string $name, LoggerInterface $logger): void
    {
        if ($name === '') {
            throw new LoggingException('Logging channel name cannot be empty.');
        }

        $this->channels[$name] = $logger;
    }

    /**
     * @return list<string>
     */
    public function channels(): array
    {
        $channels = $this->config['channels'] ?? [];

        if (!is_array($channels)) {
            return [];
        }

        return array_values(array_filter(array_keys($channels), 'is_string'));
    }

    protected function createChannel(string $name): LoggerInterface
    {
        $config = $this->channelConfig($name);
        $driver = $config['driver'] ?? $name;
        $driver = is_string($driver) && $driver !== '' ? $driver : $name;

        if ($driver === 'null') {
            return new NullLogger();
        }

        if ($driver === 'file') {
            $path = $config['path'] ?? null;
            if (!is_string($path) || $path === '') {
                throw new LoggingException("Logging channel [{$name}] requires a path.");
            }

            return new FileLogger($path);
        }

        throw new LoggingException("Unsupported logging driver [{$driver}].");
    }

    /**
     * @return array<string, mixed>
     */
    protected function channelConfig(string $name): array
    {
        $channels = $this->config['channels'] ?? null;

        if (!is_array($channels) || !isset($channels[$name]) || !is_array($channels[$name])) {
            throw new LoggingException("Logging channel [{$name}] is not configured.");
        }

        $config = [];
        foreach ($channels[$name] as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        return $config;
    }
}
