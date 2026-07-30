<?php

namespace Codemonster\Logging\Tests;

use Codemonster\DateTime\FrozenClock;
use Codemonster\Logging\FileLogger;
use Codemonster\Logging\LoggerManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggingTest extends TestCase
{
    public function test_file_logger_writes_interpolated_messages(): void
    {
        $path = sys_get_temp_dir() . '/annabel-logging-' . bin2hex(random_bytes(6)) . '/app.log';
        $clock = new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:00 UTC'));
        $logger = new FileLogger($path, $clock);

        try {
            $logger->info('User {id} registered', [
                'id' => 42,
                'role' => 'admin',
            ]);

            $contents = (string) file_get_contents($path);

            self::assertStringStartsWith('[2026-07-31 12:30:00]', $contents);
            self::assertStringContainsString('INFO: User 42 registered', $contents);
            self::assertStringContainsString('"role":"admin"', $contents);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }

            $directory = dirname($path);
            if (is_dir($directory)) {
                @rmdir($directory);
            }
        }
    }

    public function test_manager_resolves_configured_channels(): void
    {
        $path = sys_get_temp_dir() . '/annabel-logging-manager-' . bin2hex(random_bytes(6)) . '/app.log';
        $manager = new LoggerManager([
            'default' => 'file',
            'channels' => [
                'file' => [
                    'driver' => 'file',
                    'path' => $path,
                ],
                'null' => [
                    'driver' => 'null',
                ],
            ],
        ], new FrozenClock(new \DateTimeImmutable('2026-07-31 12:30:00 UTC')));

        try {
            $manager->channel()->warning('Careful');

            self::assertSame(['file', 'null'], $manager->channels());
            self::assertInstanceOf(LoggerInterface::class, $manager->channel('null'));
            $contents = (string) file_get_contents($path);

            self::assertStringStartsWith('[2026-07-31 12:30:00]', $contents);
            self::assertStringContainsString('WARNING: Careful', $contents);
        } finally {
            if (is_file($path)) {
                @unlink($path);
            }

            $directory = dirname($path);
            if (is_dir($directory)) {
                @rmdir($directory);
            }
        }
    }
}
