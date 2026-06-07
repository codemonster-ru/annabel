<?php

declare(strict_types=1);

/**
 * @return array<string, string>
 */
function annabelApiPackageDirectories(string $root): array
{
    $directories = glob($root . '/packages/*', GLOB_ONLYDIR) ?: [];
    sort($directories);

    $packages = [];
    foreach ($directories as $directory) {
        $composerFile = $directory . '/composer.json';
        if (!is_file($composerFile)) {
            continue;
        }

        $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
        $name = $composer['name'] ?? null;
        if (is_string($name) && $name !== '') {
            $packages[$name] = $directory;
        }
    }

    return $packages;
}

/**
 * @return array<string, array{api: list<string>, internal: list<string>}>
 */
function annabelApiContracts(): array
{
    return [
        'codemonster-ru/annabel' => [
            'api' => [
                '#^src/(Application|Container)\.php$#',
                '#^src/Cache/(ArrayCache|FileCache|InvalidCacheKeyException)\.php$#',
                '#^src/Console/(ArgvInput|BufferedOutput|Command|CommandRegistry|Console|ExitCode|StreamOutput)\.php$#',
                '#^src/Console/Contracts/.+\.php$#',
                '#^src/Contracts/.+\.php$#',
                '#^src/Events/(EventDispatcher|ListenerProvider)\.php$#',
                '#^src/Exceptions/.+\.php$#',
                '#^src/Http/.+\.php$#',
                '#^src/Providers/.+\.php$#',
                '#^src/Publishing/(PublishRegistry|PublishResult|ResourcePublisher)\.php$#',
                '#^src/Validation/.+\.php$#',
            ],
            'internal' => [
                '#^src/Bootstrap/.+\.php$#',
                '#^src/Console/Commands/.+\.php$#',
                '#^src/Database/.+\.php$#',
                '#^src/Logging/.+\.php$#',
            ],
        ],
        'codemonster-ru/config' => annabelApiAll(),
        'codemonster-ru/database' => annabelApiAll(),
        'codemonster-ru/dumper' => annabelApiAll(),
        'codemonster-ru/env' => annabelApiAll(),
        'codemonster-ru/errors' => annabelApiAll(),
        'codemonster-ru/http' => annabelApiAll(),
        'codemonster-ru/razor' => [
            'api' => [
                '#^src/RazorEngine\.php$#',
                '#^src/Exceptions/.+\.php$#',
            ],
            'internal' => [
                '#^src/Compiler\.php$#',
            ],
        ],
        'codemonster-ru/router' => annabelApiAll(),
        'codemonster-ru/security' => annabelApiAll(),
        'codemonster-ru/session' => annabelApiAll(),
        'codemonster-ru/ssr-bridge' => [
            'api' => [
                '#^src/SsrBridge\.php$#',
            ],
            'internal' => [
                '#^src/ProcessHelper\.php$#',
            ],
        ],
        'codemonster-ru/support' => annabelApiAll(),
        'codemonster-ru/view' => annabelApiAll(),
        'codemonster-ru/view-php' => annabelApiAll(),
        'codemonster-ru/view-ssr' => annabelApiAll(),
    ];
}

/**
 * @return array{api: list<string>, internal: list<string>}
 */
function annabelApiAll(): array
{
    return [
        'api' => ['#^src/.+\.php$#'],
        'internal' => [],
    ];
}

/**
 * @param array{api: list<string>, internal: list<string>} $contract
 */
function annabelApiClassify(string $packageName, string $relativeFile, array $contract): ?string
{
    $api = annabelApiMatchesAny($relativeFile, $contract['api']);
    $internal = annabelApiMatchesAny($relativeFile, $contract['internal']);

    if ($api && $internal) {
        throw new RuntimeException("{$packageName}: {$relativeFile} matches both api and internal contract rules");
    }

    if ($api) {
        return 'api';
    }

    return $internal ? 'internal' : null;
}

/**
 * @param list<string> $patterns
 */
function annabelApiMatchesAny(string $value, array $patterns): bool
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value) === 1) {
            return true;
        }
    }

    return false;
}

/**
 * @return list<string>
 */
function annabelApiPhpFiles(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

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
function annabelApiDeclaredSymbols(string $file): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $namespace = '';
    $symbols = [];
    $count = count($tokens);

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];

        if (!is_array($token)) {
            continue;
        }

        if ($token[0] === T_NAMESPACE) {
            $namespace = annabelApiReadName($tokens, $index + 1);
            continue;
        }

        if (!in_array($token[0], annabelApiSymbolDeclarationTokens(), true)) {
            continue;
        }

        $previousToken = annabelApiPreviousMeaningfulToken($tokens, $index);
        if ($token[0] === T_CLASS && ($previousToken === T_DOUBLE_COLON || $previousToken === T_NEW)) {
            continue;
        }

        for ($nameIndex = $index + 1; $nameIndex < $count; $nameIndex++) {
            $nameToken = $tokens[$nameIndex];
            if (is_array($nameToken) && $nameToken[0] === T_STRING) {
                $symbols[] = ltrim($namespace . '\\' . $nameToken[1], '\\');
                break;
            }

            if ($nameToken === '(' || $nameToken === '{') {
                break;
            }
        }
    }

    return $symbols;
}

/**
 * @return list<array{string, int}>
 */
function annabelApiReferencedNames(string $file): array
{
    $references = [];

    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (!is_array($token) || !in_array($token[0], annabelApiNameTokens(), true)) {
            continue;
        }

        $references[] = [$token[1], $token[2]];
    }

    return $references;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function annabelApiPreviousMeaningfulToken(array $tokens, int $index): int|string|null
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
function annabelApiReadName(array $tokens, int $start): string
{
    $name = '';

    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];

        if (is_array($token) && in_array($token[0], annabelApiNameTokens(), true)) {
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
function annabelApiNameTokens(): array
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
function annabelApiSymbolDeclarationTokens(): array
{
    return array_values(array_filter([
        T_CLASS,
        T_INTERFACE,
        T_TRAIT,
        defined('T_ENUM') ? T_ENUM : null,
    ], is_int(...)));
}

function annabelApiRelativePath(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
}
