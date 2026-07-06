<?php

declare(strict_types=1);

/**
 * Verifies that references between monorepo packages are declared in Composer.
 *
 * Production code may depend on required or explicitly suggested packages.
 * Test code may additionally depend on require-dev packages.
 */

$root = dirname(__DIR__);
$packageDirectories = glob($root . '/packages/*', GLOB_ONLYDIR) ?: [];
$packages = [];
$classOwners = [];
$violations = [];

foreach ($packageDirectories as $packageDirectory) {
    $composerFile = $packageDirectory . '/composer.json';

    if (!is_file($composerFile)) {
        continue;
    }

    $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
    $packageName = $composer['name'] ?? null;

    if (!is_string($packageName) || $packageName === '') {
        $violations[] = relativePath($root, $composerFile) . ': missing Composer package name';
        continue;
    }

    $packages[$packageName] = [
        'directory' => $packageDirectory,
        'require' => array_keys($composer['require'] ?? []),
        'require-dev' => array_keys($composer['require-dev'] ?? []),
        'suggest' => array_keys($composer['suggest'] ?? []),
        'source-paths' => autoloadPaths($packageDirectory, $composer['autoload']['psr-4'] ?? []),
        'test-paths' => autoloadPaths($packageDirectory, $composer['autoload-dev']['psr-4'] ?? []),
    ];
}

foreach ($packages as $packageName => $package) {
    foreach ($package['source-paths'] as $path) {
        foreach (phpFiles($path) as $file) {
            foreach (declaredClasses($file) as $class) {
                if (isset($classOwners[$class]) && $classOwners[$class] !== $packageName) {
                    $violations[] = sprintf(
                        '%s: class %s is also declared by %s',
                        relativePath($root, $file),
                        $class,
                        $classOwners[$class],
                    );
                    continue;
                }

                $classOwners[$class] = $packageName;
            }
        }
    }
}

foreach ($packages as $packageName => $package) {
    $productionDependencies = array_fill_keys(
        array_merge($package['require'], $package['suggest']),
        true,
    );
    $testDependencies = $productionDependencies + array_fill_keys($package['require-dev'], true);

    inspectReferences(
        $root,
        $packageName,
        $package['source-paths'],
        $productionDependencies,
        $classOwners,
        'production',
        $violations,
    );
    inspectReferences(
        $root,
        $packageName,
        $package['test-paths'],
        $testDependencies,
        $classOwners,
        'test',
        $violations,
    );
}

if ($violations !== []) {
    fwrite(STDERR, "Architecture violations:\n\n");

    foreach (array_unique($violations) as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }

    exit(1);
}

printf(
    "Architecture boundaries are valid (%d packages, %d local classes).\n",
    count($packages),
    count($classOwners),
);

/**
 * @param array<string, string|string[]> $mapping
 * @return list<string>
 */
function autoloadPaths(string $packageDirectory, array $mapping): array
{
    $paths = [];

    foreach ($mapping as $path) {
        foreach ((array) $path as $autoloadPath) {
            $fullPath = $packageDirectory . '/' . rtrim($autoloadPath, '/');

            if (is_dir($fullPath)) {
                $paths[] = $fullPath;
            }
        }
    }

    return array_values(array_unique($paths));
}

/**
 * @return list<string>
 */
function phpFiles(string $directory): array
{
    $files = [];
    $directories = [$directory];

    while ($currentDirectory = array_pop($directories)) {
        $entries = scandir($currentDirectory);

        if ($entries === false) {
            continue;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $currentDirectory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $directories[] = $path;
                continue;
            }

            if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $files[] = $path;
            }
        }
    }

    sort($files);

    return $files;
}

/**
 * @return list<string>
 */
function declaredClasses(string $file): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $namespace = '';
    $classes = [];
    $count = count($tokens);

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];

        if (!is_array($token)) {
            continue;
        }

        if ($token[0] === T_NAMESPACE) {
            $namespace = readName($tokens, $index + 1);
            continue;
        }

        if (!in_array($token[0], classDeclarationTokens(), true)) {
            continue;
        }

        $previousToken = previousMeaningfulToken($tokens, $index);

        if (
            $token[0] === T_CLASS
            && (
                $previousToken === T_DOUBLE_COLON
                || $previousToken === T_NEW
            )
        ) {
            continue;
        }

        for ($nameIndex = $index + 1; $nameIndex < $count; $nameIndex++) {
            $nameToken = $tokens[$nameIndex];

            if (is_array($nameToken) && $nameToken[0] === T_STRING) {
                $classes[] = ltrim($namespace . '\\' . $nameToken[1], '\\');
                break;
            }

            if ($nameToken === '(' || $nameToken === '{') {
                break;
            }
        }
    }

    return $classes;
}

/**
 * @param list<string> $paths
 * @param array<string, true> $allowedDependencies
 * @param array<string, string> $classOwners
 * @param list<string> $violations
 */
function inspectReferences(
    string $root,
    string $packageName,
    array $paths,
    array $allowedDependencies,
    array $classOwners,
    string $scope,
    array &$violations,
): void {
    foreach ($paths as $path) {
        foreach (phpFiles($path) as $file) {
            foreach (referencedNames($file) as [$reference, $line]) {
                $owner = $classOwners[ltrim($reference, '\\')] ?? null;

                if ($owner === null || $owner === $packageName || isset($allowedDependencies[$owner])) {
                    continue;
                }

                $violations[] = sprintf(
                    '%s:%d: %s code references %s from undeclared package %s',
                    relativePath($root, $file),
                    $line,
                    $scope,
                    $reference,
                    $owner,
                );
            }
        }
    }
}

/**
 * @return list<array{string, int}>
 */
function referencedNames(string $file): array
{
    $references = [];

    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (!is_array($token) || !in_array($token[0], nameTokens(), true)) {
            continue;
        }

        $references[] = [$token[1], $token[2]];
    }

    return $references;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function previousMeaningfulToken(array $tokens, int $index): int|string|null
{
    for ($previousIndex = $index - 1; $previousIndex >= 0; $previousIndex--) {
        $token = $tokens[$previousIndex];

        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return is_array($token) ? $token[0] : $token;
    }

    return null;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function readName(array $tokens, int $start): string
{
    $name = '';

    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];

        if (is_array($token) && in_array($token[0], nameTokens(), true)) {
            $name .= $token[1];
            continue;
        }

        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }

        if ($token === '\\') {
            $name .= '\\';
            continue;
        }

        break;
    }

    return trim($name, '\\');
}

/**
 * @return list<int>
 */
function nameTokens(): array
{
    return array_values(array_filter([
        T_STRING,
        defined('T_NAME_QUALIFIED') ? T_NAME_QUALIFIED : null,
        defined('T_NAME_FULLY_QUALIFIED') ? T_NAME_FULLY_QUALIFIED : null,
    ], is_int(...)));
}

/**
 * @return list<int>
 */
function classDeclarationTokens(): array
{
    return array_filter([
        T_CLASS,
        T_INTERFACE,
        T_TRAIT,
        defined('T_ENUM') ? T_ENUM : null,
    ], is_int(...));
}

function relativePath(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
}
