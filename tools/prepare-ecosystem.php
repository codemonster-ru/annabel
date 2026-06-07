<?php

declare(strict_types=1);

/**
 * Builds a temporary skeleton copy for ecosystem acceptance tests.
 *
 * The skeleton in the repository must stay release-clean: no vendor directory,
 * lock files, generated caches, or runtime storage. Acceptance tests still need
 * installed dependencies, so they run against this disposable copy.
 */

$root = dirname(__DIR__);
$source = $root . '/skeleton/annabel-skeleton';
$target = '/tmp/annabel-skeleton-acceptance';

if (!is_dir($source)) {
    fwrite(STDERR, "Skeleton source is missing: {$source}\n");
    exit(1);
}

removePath($target);
mkdir($target, 0777, true);
copyDirectory($source, $target);

$manifest = $target . '/composer.dev.json';
$contents = (string) file_get_contents($manifest);
$contents = str_replace('../../packages/*', $root . '/packages/*', $contents);
file_put_contents($manifest, $contents);

fwrite(STDOUT, "Prepared ecosystem skeleton at {$target}.\n");

function copyDirectory(string $source, string $target): void
{
    $entries = scandir($source);
    if ($entries === false) {
        throw new RuntimeException("Unable to read directory: {$source}");
    }

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || shouldSkip($entry)) {
            continue;
        }

        $sourcePath = $source . DIRECTORY_SEPARATOR . $entry;
        $targetPath = $target . DIRECTORY_SEPARATOR . $entry;

        if (is_dir($sourcePath)) {
            mkdir($targetPath, 0777, true);
            copyDirectory($sourcePath, $targetPath);
            continue;
        }

        copy($sourcePath, $targetPath);
    }
}

function shouldSkip(string $entry): bool
{
    return in_array($entry, [
        '.DS_Store',
        'composer.dev.lock',
        'vendor',
    ], true);
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
