<?php

namespace Codemonster\Logging;

use Codemonster\DateTime\DateTime;
use Codemonster\DateTime\SystemClock;
use Psr\Clock\ClockInterface;
use Psr\Log\AbstractLogger;
use Stringable;

class FileLogger extends AbstractLogger
{
    protected ClockInterface $clock;

    public function __construct(protected string $path, ?ClockInterface $clock = null)
    {
        $this->clock = $clock ?? new SystemClock(date_default_timezone_get());
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $level = is_string($level) ? $level : (is_scalar($level) ? (string) $level : 'unknown');
        $context = $this->normalizeContext($context);
        $directory = dirname($this->path);

        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new LoggingException("Unable to create log directory: {$directory}");
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            $this->clock->now()->format(DateTime::DATABASE_FORMAT),
            strtoupper($level),
            $this->interpolate((string) $message, $context),
            $this->formatContext($context),
        );

        if (file_put_contents($this->path, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new LoggingException("Unable to write log file: {$this->path}");
        }
    }

    /** @param array<mixed, mixed> $context
     *  @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];
        foreach ($context as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /** @param array<string, mixed> $context */
    protected function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if ($value === null || is_scalar($value) || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }

    /** @param array<string, mixed> $context */
    protected function formatContext(array $context): string
    {
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $e = $context['exception'];
            $context['exception'] = [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $json === false || $json === '[]' ? '' : $json;
    }
}
