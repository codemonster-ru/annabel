<?php

declare(strict_types=1);

/**
 * Verifies a stable snapshot of public API signatures.
 *
 * Update intentionally after a reviewed public API change:
 *   composer api:snapshot:update
 */

require __DIR__ . '/api-support.php';

$root = dirname(__DIR__);
$snapshotFile = $root . '/maintenance/api-snapshot.json';
$update = in_array('--update', $argv, true);
$current = buildApiSnapshot($root);

if ($update) {
    file_put_contents(
        $snapshotFile,
        json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
    );
    printf("API snapshot updated (%d symbols).\n", count($current['symbols']));
    exit(0);
}

if (!is_file($snapshotFile)) {
    fwrite(STDERR, "API snapshot is missing. Run `composer api:snapshot:update`.\n");
    exit(1);
}

$expected = json_decode((string) file_get_contents($snapshotFile), true, flags: JSON_THROW_ON_ERROR);

if ($expected !== $current) {
    fwrite(STDERR, "API snapshot changed. Review the public API change and run `composer api:snapshot:update` if intentional.\n");
    exit(1);
}

printf("API snapshot is valid (%d symbols).\n", count($current['symbols']));

/**
 * @return array{version: int, symbols: array<string, mixed>}
 */
function buildApiSnapshot(string $root): array
{
    $packages = annabelApiPackageDirectories($root);
    $contracts = annabelApiContracts();
    $symbols = [];

    foreach ($packages as $packageName => $packageDirectory) {
        $contract = $contracts[$packageName] ?? null;
        if ($contract === null) {
            continue;
        }

        foreach (annabelApiPhpFiles($packageDirectory . '/src') as $file) {
            $relativeFile = annabelApiRelativePath($packageDirectory, $file);
            if (annabelApiClassify($packageName, $relativeFile, $contract) !== 'api') {
                continue;
            }

            foreach (parseApiSymbols($file) as $symbol => $metadata) {
                $symbols[$symbol] = ['package' => $packageName, 'file' => $relativeFile] + $metadata;
            }
        }
    }

    ksort($symbols);

    return [
        'version' => 1,
        'symbols' => $symbols,
    ];
}

/**
 * @return array<string, array<string, mixed>>
 */
function parseApiSymbols(string $file): array
{
    $tokens = token_get_all((string) file_get_contents($file));
    $namespace = '';
    $symbols = [];
    $classRanges = [];
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

        $nameIndex = findNextToken($tokens, $index + 1, [T_STRING]);
        if ($nameIndex === null) {
            continue;
        }

        $bodyStart = findNextLiteral($tokens, $nameIndex + 1, '{');
        if ($bodyStart === null) {
            continue;
        }

        $bodyEnd = findMatchingBrace($tokens, $bodyStart);
        if ($bodyEnd === null) {
            continue;
        }
        $classRanges[] = [$bodyStart, $bodyEnd];

        $name = tokenText($tokens[$nameIndex]);
        $symbol = ltrim($namespace . '\\' . $name, '\\');
        $headerTokens = array_slice($tokens, $index, $bodyStart - $index);

        $symbols[$symbol] = [
            'kind' => symbolKind($token[0]),
            'abstract' => hasTokenBefore($tokens, $index, T_ABSTRACT),
            'final' => hasTokenBefore($tokens, $index, T_FINAL),
            'extends' => readHeaderNames($headerTokens, T_EXTENDS),
            'implements' => readHeaderNames($headerTokens, T_IMPLEMENTS),
            'constants' => parseClassConstants($tokens, $bodyStart + 1, $bodyEnd),
            'properties' => parseClassProperties($tokens, $bodyStart + 1, $bodyEnd),
            'methods' => parseClassMethods($tokens, $bodyStart + 1, $bodyEnd),
        ];
    }

    foreach (parseApiFunctions($tokens, $namespace, $classRanges) as $symbol => $metadata) {
        $symbols[$symbol] = $metadata;
    }

    return $symbols;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 * @param list<array{int, int}> $classRanges
 * @return array<string, array<string, mixed>>
 */
function parseApiFunctions(array $tokens, string $namespace, array $classRanges): array
{
    $functions = [];
    for ($index = 0, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];
        if (
            !is_array($token)
            || $token[0] !== T_FUNCTION
            || indexInRanges($index, $classRanges)
            || hasMethodModifierBefore($tokens, $index)
        ) {
            continue;
        }

        $nameIndex = functionNameIndex($tokens, $index + 1);
        if ($nameIndex === null) {
            continue;
        }

        $paramsStart = findNextLiteral($tokens, $nameIndex + 1, '(');
        if ($paramsStart === null) {
            continue;
        }

        $paramsEnd = findMatchingParen($tokens, $paramsStart);
        if ($paramsEnd === null) {
            continue;
        }

        $name = tokenText($tokens[$nameIndex]);
        $symbol = ltrim($namespace . '\\' . $name, '\\');

        $functions[$symbol] = [
            'kind' => 'function',
            'abstract' => false,
            'final' => false,
            'extends' => [],
            'implements' => [],
            'constants' => [],
            'properties' => [],
            'methods' => [
                '__invoke' => [
                    'visibility' => 'public',
                    'static' => false,
                    'final' => false,
                    'abstract' => false,
                    'parameters' => normalizeSignatureTokens(array_slice($tokens, $paramsStart + 1, $paramsEnd - $paramsStart - 1)),
                    'return' => readReturnType($tokens, $paramsEnd + 1, $count),
                ],
            ],
        ];
    }

    return $functions;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 * @return array<string, string>
 */
function parseClassConstants(array $tokens, int $start, int $end): array
{
    $constants = [];
    for ($index = $start, $depth = 0; $index < $end; $index++) {
        $token = $tokens[$index];
        $depth += depthDelta($token);
        if ($depth !== 0 || !is_array($token) || $token[0] !== T_CONST) {
            continue;
        }

        $visibility = visibilityBefore($tokens, $index);
        if (!in_array($visibility, ['public', 'protected'], true)) {
            continue;
        }

        for ($nameIndex = $index + 1; $nameIndex < $end; $nameIndex++) {
            $nameToken = $tokens[$nameIndex];
            if ($nameToken === ';') {
                break;
            }
            if (is_array($nameToken) && $nameToken[0] === T_STRING) {
                $constants[$nameToken[1]] = $visibility;
            }
        }
    }

    ksort($constants);

    return $constants;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 * @return array<string, string>
 */
function parseClassProperties(array $tokens, int $start, int $end): array
{
    $properties = [];
    for ($index = $start, $depth = 0, $parenDepth = 0, $bracketDepth = 0; $index < $end; $index++) {
        $token = $tokens[$index];
        $depth += depthDelta($token);

        if ($token === '(') {
            $parenDepth++;
            continue;
        }
        if ($token === ')') {
            $parenDepth--;
            continue;
        }
        if ($token === '[') {
            $bracketDepth++;
            continue;
        }
        if ($token === ']') {
            $bracketDepth--;
            continue;
        }

        if (
            $depth !== 0
            || $parenDepth !== 0
            || $bracketDepth !== 0
            || !is_array($token)
            || $token[0] !== T_VARIABLE
        ) {
            continue;
        }

        $visibility = visibilityBefore($tokens, $index);
        if (!in_array($visibility, ['public', 'protected'], true)) {
            continue;
        }

        $properties[$token[1]] = trim($visibility . (hasModifierBefore($tokens, $index, T_STATIC) ? ' static' : ''));
    }

    ksort($properties);

    return $properties;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 * @return array<string, array<string, string|bool>>
 */
function parseClassMethods(array $tokens, int $start, int $end): array
{
    $methods = [];
    for ($index = $start, $depth = 0; $index < $end; $index++) {
        $token = $tokens[$index];
        $depth += depthDelta($token);
        if ($depth !== 0 || !is_array($token) || $token[0] !== T_FUNCTION) {
            continue;
        }

        $visibility = visibilityBefore($tokens, $index);
        if (!in_array($visibility, ['public', 'protected'], true)) {
            continue;
        }

        $nameIndex = findNextToken($tokens, $index + 1, [T_STRING]);
        if ($nameIndex === null) {
            continue;
        }

        $paramsStart = findNextLiteral($tokens, $nameIndex + 1, '(');
        if ($paramsStart === null) {
            continue;
        }

        $paramsEnd = findMatchingParen($tokens, $paramsStart);
        if ($paramsEnd === null) {
            continue;
        }

        $methods[tokenText($tokens[$nameIndex])] = [
            'visibility' => $visibility,
            'static' => hasModifierBefore($tokens, $index, T_STATIC),
            'final' => hasModifierBefore($tokens, $index, T_FINAL),
            'abstract' => hasModifierBefore($tokens, $index, T_ABSTRACT),
            'parameters' => normalizeSignatureTokens(array_slice($tokens, $paramsStart + 1, $paramsEnd - $paramsStart - 1)),
            'return' => readReturnType($tokens, $paramsEnd + 1, $end),
        ];
    }

    ksort($methods);

    return $methods;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function readReturnType(array $tokens, int $start, int $end): string
{
    for ($index = $start; $index < $end; $index++) {
        $token = $tokens[$index];
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        if ($token !== ':') {
            return '';
        }

        $typeTokens = [];
        for ($typeIndex = $index + 1; $typeIndex < $end; $typeIndex++) {
            $typeToken = $tokens[$typeIndex];
            if ($typeToken === '{' || $typeToken === ';') {
                break;
            }
            $typeTokens[] = $typeToken;
        }

        return normalizeSignatureTokens($typeTokens);
    }

    return '';
}

/**
 * @param list<array{int, string, int}|string> $tokens
 */
function normalizeSignatureTokens(array $tokens): string
{
    $parts = [];
    foreach ($tokens as $token) {
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        $parts[] = is_array($token) && $token[0] === T_VARIABLE ? '$param' : tokenText($token);
    }

    return implode('', $parts);
}

/**
 * @param list<array{int, string, int}|string> $headerTokens
 * @return list<string>
 */
function readHeaderNames(array $headerTokens, int $keyword): array
{
    $names = [];
    for ($index = 0, $count = count($headerTokens); $index < $count; $index++) {
        $token = $headerTokens[$index];
        if (!is_array($token) || $token[0] !== $keyword) {
            continue;
        }

        for ($nameIndex = $index + 1; $nameIndex < $count; $nameIndex++) {
            $nameToken = $headerTokens[$nameIndex];
            if ($nameToken === '{' || (is_array($nameToken) && in_array($nameToken[0], [T_IMPLEMENTS, T_EXTENDS], true))) {
                break;
            }

            if (is_array($nameToken) && in_array($nameToken[0], annabelApiNameTokens(), true)) {
                $names[] = ltrim($nameToken[1], '\\');
            }
        }
    }

    sort($names);

    return array_values(array_unique($names));
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function visibilityBefore(array $tokens, int $index): string
{
    for ($previous = $index - 1; $previous >= 0; $previous--) {
        $token = $tokens[$previous];
        if ($token === ';' || $token === '{' || $token === '}') {
            return 'public';
        }
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        if (is_array($token) && $token[0] === T_PUBLIC) {
            return 'public';
        }
        if (is_array($token) && $token[0] === T_PROTECTED) {
            return 'protected';
        }
        if (is_array($token) && $token[0] === T_PRIVATE) {
            return 'private';
        }
    }

    return 'public';
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function hasModifierBefore(array $tokens, int $index, int $modifier): bool
{
    for ($previous = $index - 1; $previous >= 0; $previous--) {
        $token = $tokens[$previous];
        if ($token === ';' || $token === '{' || $token === '}') {
            return false;
        }
        if (is_array($token) && $token[0] === $modifier) {
            return true;
        }
    }

    return false;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function hasMethodModifierBefore(array $tokens, int $index): bool
{
    for ($previous = $index - 1; $previous >= 0; $previous--) {
        $token = $tokens[$previous];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }

        return is_array($token) && in_array($token[0], [
            T_PUBLIC,
            T_PROTECTED,
            T_PRIVATE,
            T_STATIC,
            T_FINAL,
            T_ABSTRACT,
        ], true);
    }

    return false;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function hasTokenBefore(array $tokens, int $index, int $needle): bool
{
    for ($previous = $index - 1; $previous >= 0; $previous--) {
        $token = $tokens[$previous];
        if (is_array($token) && $token[0] === $needle) {
            return true;
        }
        if (!is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            return false;
        }
    }

    return false;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 * @param list<int> $types
 */
function findNextToken(array $tokens, int $start, array $types): ?int
{
    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        if (is_array($tokens[$index]) && in_array($tokens[$index][0], $types, true)) {
            return $index;
        }
    }

    return null;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function functionNameIndex(array $tokens, int $start): ?int
{
    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        $token = $tokens[$index];
        if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            continue;
        }
        if ($token === '&') {
            continue;
        }

        return is_array($token) && $token[0] === T_STRING ? $index : null;
    }

    return null;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function findNextLiteral(array $tokens, int $start, string $literal): ?int
{
    for ($index = $start, $count = count($tokens); $index < $count; $index++) {
        if ($tokens[$index] === $literal) {
            return $index;
        }
    }

    return null;
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function findMatchingBrace(array $tokens, int $start): ?int
{
    return findMatchingLiteral($tokens, $start, '{', '}');
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function findMatchingParen(array $tokens, int $start): ?int
{
    return findMatchingLiteral($tokens, $start, '(', ')');
}

/**
 * @param array<int, array{int, string, int}|string> $tokens
 */
function findMatchingLiteral(array $tokens, int $start, string $open, string $close): ?int
{
    for ($index = $start, $depth = 0, $count = count($tokens); $index < $count; $index++) {
        if ($tokens[$index] === $open) {
            $depth++;
            continue;
        }
        if ($tokens[$index] === $close) {
            $depth--;
            if ($depth === 0) {
                return $index;
            }
        }
    }

    return null;
}

/**
 * @param list<array{int, int}> $ranges
 */
function indexInRanges(int $index, array $ranges): bool
{
    foreach ($ranges as [$start, $end]) {
        if ($index > $start && $index < $end) {
            return true;
        }
    }

    return false;
}

function depthDelta(array|string $token): int
{
    if ($token === '{') {
        return 1;
    }
    if ($token === '}') {
        return -1;
    }

    return 0;
}

function symbolKind(int $token): string
{
    if (defined('T_ENUM') && $token === T_ENUM) {
        return 'enum';
    }

    return match ($token) {
        T_INTERFACE => 'interface',
        T_TRAIT => 'trait',
        default => 'class',
    };
}

function tokenText(array|string $token): string
{
    return is_array($token) ? $token[1] : $token;
}
