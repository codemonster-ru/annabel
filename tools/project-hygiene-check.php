<?php

declare(strict_types=1);

/**
 * Verifies release hygiene that static analysis does not see:
 * package metadata, quality gates, documentation, and skeleton artifacts.
 */

$root = dirname(__DIR__);
$violations = [];

foreach (packageDirectories($root) as $packageDirectory) {
    inspectPackage($root, $packageDirectory, $violations);
}

inspectSkeleton($root, $root . '/skeleton/annabel-skeleton', $violations);

if ($violations !== []) {
    fwrite(STDERR, "Project hygiene violations:\n\n");

    foreach ($violations as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }

    exit(1);
}

printf("Project hygiene is valid (%d packages, skeleton checked).\n", count(packageDirectories($root)));

/**
 * @return list<string>
 */
function packageDirectories(string $root): array
{
    $directories = glob($root . '/packages/*', GLOB_ONLYDIR) ?: [];
    sort($directories);

    return array_values(array_filter(
        $directories,
        static fn (string $directory): bool => is_file($directory . '/composer.json'),
    ));
}

/**
 * @param list<string> $violations
 */
function inspectPackage(string $root, string $packageDirectory, array &$violations): void
{
    $composerFile = $packageDirectory . '/composer.json';
    $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
    $packageName = $composer['name'] ?? null;
    $relativeDirectory = relativePath($root, $packageDirectory);

    if (!is_string($packageName) || !str_starts_with($packageName, 'codemonster-ru/')) {
        $violations[] = "{$relativeDirectory}/composer.json: package name must use the codemonster-ru vendor";
    }

    if (($composer['type'] ?? null) !== 'library') {
        $violations[] = "{$relativeDirectory}/composer.json: package type must be library";
    }

    if (($composer['license'] ?? null) !== 'MIT') {
        $violations[] = "{$relativeDirectory}/composer.json: package license must be MIT";
    }

    if (!isset($composer['autoload']['psr-4']) || !is_array($composer['autoload']['psr-4'])) {
        $violations[] = "{$relativeDirectory}/composer.json: package must define PSR-4 autoload";
    }

    if (!is_file($packageDirectory . '/README.md')) {
        $violations[] = "{$relativeDirectory}/README.md: package must document its public usage";
    }

    if (!is_file($packageDirectory . '/CHANGELOG.md')) {
        $violations[] = "{$relativeDirectory}/CHANGELOG.md: package must document release changes";
    }

    $phpstanFile = $packageDirectory . '/phpstan.neon';
    if (!is_file($phpstanFile)) {
        $violations[] = "{$relativeDirectory}/phpstan.neon: package must have a PHPStan config";

        return;
    }

    if (!preg_match('/^\s*level:\s*max\s*$/m', (string) file_get_contents($phpstanFile))) {
        $violations[] = "{$relativeDirectory}/phpstan.neon: package must run PHPStan at level max";
    }
}

/**
 * @param list<string> $violations
 */
function inspectSkeleton(string $root, string $skeletonDirectory, array &$violations): void
{
    if (!is_dir($skeletonDirectory)) {
        $violations[] = 'skeleton/annabel-skeleton: skeleton application is missing';

        return;
    }

    foreach (requiredSkeletonFiles() as $file) {
        if (!is_file($skeletonDirectory . '/' . $file)) {
            $violations[] = "skeleton/annabel-skeleton/{$file}: required skeleton file is missing";
        }
    }

    foreach (forbiddenSkeletonPaths() as $path) {
        if (file_exists($skeletonDirectory . '/' . $path)) {
            $violations[] = "skeleton/annabel-skeleton/{$path}: generated artifact must not be shipped";
        }
    }

    foreach (forbiddenRepositoryPaths() as $path) {
        if (file_exists($root . '/' . $path)) {
            $violations[] = "{$path}: generated artifact must not be kept in the repository root";
        }
    }
}

/**
 * @return list<string>
 */
function requiredSkeletonFiles(): array
{
    return [
        '.env.example',
        'README.md',
        'bootstrap/app.php',
        'composer.json',
        'config/app.php',
        'config/cache.php',
        'config/database.php',
        'config/filesystem.php',
        'config/http-client.php',
        'config/logging.php',
        'config/mail.php',
        'config/queue.php',
        'config/security.php',
        'config/session.php',
        'config/validation.php',
        'public/index.php',
        'routes/schedule.php',
        'routes/web.php',
    ];
}

/**
 * @return list<string>
 */
function forbiddenSkeletonPaths(): array
{
    return [
        '.DS_Store',
        'bootstrap/cache/packages.php',
        'composer.dev.lock',
        'storage/sessions',
        'vendor',
    ];
}

/**
 * @return list<string>
 */
function forbiddenRepositoryPaths(): array
{
    return [
        '.DS_Store',
        '.phpunit.result.cache',
        'docker/.DS_Store',
        'packages/.DS_Store',
        'skeleton/.DS_Store',
    ];
}

function relativePath(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
}
