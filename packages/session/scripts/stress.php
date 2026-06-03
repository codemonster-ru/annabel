<?php

require __DIR__ . '/../vendor/autoload.php';

use Codemonster\Session\Handlers\RedisSessionHandler;
use Codemonster\Session\Session;

$iterations = (int) ($argv[1] ?? 10000);
$driver = $argv[2] ?? 'array';
$minOps = isset($argv[3]) ? (float) $argv[3] : 0.0;
$maxSeconds = isset($argv[4]) ? (float) $argv[4] : 0.0;

if ($driver === 'redis') {
    if (!extension_loaded('redis')) {
        fwrite(STDERR, "ext-redis is required for redis stress test.\n");
        exit(1);
    }

    $host = getenv('REDIS_HOST') ?: '127.0.0.1';
    $port = (int) (getenv('REDIS_PORT') ?: 6379);

    $redis = new Redis();
    if (!$redis->connect($host, $port)) {
        fwrite(STDERR, "Unable to connect to Redis at {$host}:{$port}\n");
        exit(1);
    }

    $handler = new RedisSessionHandler($redis, 'sess_', 0);
    Session::start(customHandler: $handler);
} else {
    Session::start($driver);
}

$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    Session::put('k' . $i, $i);
    Session::get('k' . $i);
}

$elapsed = microtime(true) - $start;
$opsPerSec = $iterations / max($elapsed, 0.0001);

echo "Iterations: {$iterations}\n";
echo "Driver: {$driver}\n";
echo "Elapsed: " . number_format($elapsed, 4) . "s\n";
echo "Ops/sec: " . number_format($opsPerSec, 2) . "\n";

if ($minOps > 0 && $opsPerSec < $minOps) {
    fwrite(STDERR, "Ops/sec ниже порога: {$minOps}\n");
    exit(2);
}

if ($maxSeconds > 0 && $elapsed > $maxSeconds) {
    fwrite(STDERR, "Время выполнения превышает порог: {$maxSeconds}\n");
    exit(3);
}
