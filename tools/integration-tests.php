<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$workdir = '/tmp/annabel-integration-tests';

removePath($workdir);
mkdir($workdir, 0777, true);

$manifest = [
    'name' => 'codemonster-ru/annabel-integration-tests',
    'type' => 'project',
    'repositories' => [
        [
            'type' => 'path',
            'url' => $root . '/packages/*',
            'canonical' => true,
            'options' => [
                'symlink' => true,
            ],
        ],
    ],
    'require' => [
        'php' => '>=8.2',
        'codemonster-ru/session' => 'dev-main',
        'codemonster-ru/security' => 'dev-main',
        'codemonster-ru/database' => 'dev-main',
        'phpunit/phpunit' => '^9.6 || ^10.5 || ^11.0 || ^12.0',
    ],
    'minimum-stability' => 'dev',
    'prefer-stable' => true,
];

file_put_contents(
    $workdir . '/composer.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
);

run(sprintf(
    'composer update --working-dir=%s --no-interaction --prefer-dist --no-progress',
    escapeshellarg($workdir),
), 'Unable to install integration test dependencies.');

$commands = [
    [
        'package' => 'packages/session',
        'args' => [
            'tests/RedisSessionHandlerIntegrationTest.php',
            'tests/RedisSentinelSessionHandlerTest.php',
            'tests/RedisClusterSessionHandlerTest.php',
        ],
    ],
    [
        'package' => 'packages/security',
        'args' => [
            'tests/RateLimiting/Storage/RedisThrottleStorageTest.php',
            'tests/RateLimiting/Storage/DatabaseThrottleStorageTest.php',
        ],
    ],
];

foreach ($commands as $command) {
    $package = $command['package'];
    $files = array_map(
        static fn (string $file): string => $package . '/' . $file,
        $command['args'],
    );
    $args = implode(' ', array_map('escapeshellarg', $files));
    $cmd = sprintf(
        '%s --colors=always --testdox --fail-on-skipped --configuration %s --bootstrap %s %s',
        escapeshellarg($workdir . '/vendor/bin/phpunit'),
        escapeshellarg($root . '/' . $package . '/phpunit.xml.dist'),
        escapeshellarg($workdir . '/vendor/autoload.php'),
        $args,
    );

    run($cmd, sprintf('Integration tests failed for %s.', $package));
}

echo "Integration tests passed.\n";

function run(string $command, string $failureMessage): void
{
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        fwrite(STDERR, $failureMessage . PHP_EOL);
        exit($exitCode);
    }
}

function removePath(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (!is_dir($path)) {
        unlink($path);

        return;
    }

    $entries = scandir($path);
    if ($entries === false) {
        throw new RuntimeException("Unable to read directory: {$path}");
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        removePath($path . DIRECTORY_SEPARATOR . $entry);
    }

    rmdir($path);
}
