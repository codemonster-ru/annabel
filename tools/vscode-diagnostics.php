<?php

declare(strict_types=1);

/**
 * Emits VS Code problem-matcher friendly diagnostics for local development.
 *
 * Output format:
 *   relative/path.php:line:message
 */

$root = dirname(__DIR__);
$args = array_slice($argv, 1);
$strict = in_array('--strict', $args, true);
$checkPackageTests = in_array('--package-tests', $args, true);
$exitCode = 0;
$phpFiles = phpFiles($root);
$phpstanTargets = phpstanTargets($root);
$packageTestTargets = packagePhpstanTestTargets($root, $phpstanTargets['ready']);
$styleDiagnostics = 0;
$cmsAssetDiagnostics = 0;
$jsonDiagnostics = 0;
$javascriptDiagnostics = 0;
$composerDiagnostics = 0;
$skeletonAssetDiagnostics = 0;
$phpstanCoverageDiagnostics = 0;

progress('Starting Annabel diagnostics' . ($strict ? ' (strict mode)' : ''));
progress(sprintf('PHP syntax: checking %d files', count($phpFiles)));
foreach ($phpFiles as $file) {
    $diagnostic = lintPhpFile($root, $file);

    if ($diagnostic !== null) {
        fwrite(STDOUT, $diagnostic . PHP_EOL);
        $exitCode = 1;
    }
}

progress(sprintf(
    'PHPStan: analysing %d targets%s',
    count($phpstanTargets['ready']),
    $phpstanTargets['skipped'] === [] ? '' : sprintf(', %d skipped', count($phpstanTargets['skipped'])),
));
foreach ($phpstanTargets['ready'] as $target) {
    progress('PHPStan: ' . relativePath($root, $target['directory']));
    $status = runPhpstan($root, $target);

    if ($status !== 0) {
        $exitCode = 1;
    }
}

if ($checkPackageTests) {
    progress(sprintf('PHPStan package tests: analysing %d targets', count($packageTestTargets)));
    foreach ($packageTestTargets as $target) {
        progress('PHPStan package tests: ' . relativePath($root, $target['directory']) . '/tests');
        $status = runPhpstan($root, $target, ['tests']);

        if ($status !== 0) {
            $exitCode = 1;
        }
    }
}

progress('PHPStan coverage: checking analysed file coverage' . ($strict ? ' strictly' : ''));
$phpstanCoverageDiagnostics = checkPhpstanCoverage($root, $phpFiles, $strict);

progress('PHP-CS-Fixer: checking formatting');
$styleDiagnostics = runPhpCsFixer($root);
if ($styleDiagnostics > 0) {
    $exitCode = 1;
}

progress('CMS assets: building admin and setup bundles');
$cmsAssetDiagnostics = runCmsAssetBuilds($root);
if ($cmsAssetDiagnostics > 0) {
    $exitCode = 1;
}

progress('JSON: validating files');
$jsonDiagnostics = lintJsonFiles($root);
if ($jsonDiagnostics > 0) {
    $exitCode = 1;
}

progress('JavaScript: checking syntax');
$javascriptDiagnostics = lintJavaScriptFiles($root);
if ($javascriptDiagnostics > 0) {
    $exitCode = 1;
}

progress('Composer: validating manifests');
$composerDiagnostics = validateComposerManifests($root);
if ($composerDiagnostics > 0) {
    $exitCode = 1;
}

progress('Skeleton assets: building bundle');
$skeletonAssetDiagnostics = runSkeletonAssetBuild($root);
if ($skeletonAssetDiagnostics > 0) {
    $exitCode = 1;
}

fwrite(STDERR, sprintf(
    "Annabel diagnostics: %d PHP files linted, %d PHPStan targets analysed, %d PHPStan test targets analysed, %d PHPStan targets skipped (missing vendor/bin/phpstan), %d PHP files outside PHPStan coverage, %d PHP-CS-Fixer files flagged, %d CMS asset builds failed, %d JSON files invalid, %d JavaScript files invalid, %d Composer manifests invalid, %d skeleton asset builds failed.\n",
    count($phpFiles),
    count($phpstanTargets['ready']),
    $checkPackageTests ? count($packageTestTargets) : 0,
    count($phpstanTargets['skipped']),
    $phpstanCoverageDiagnostics,
    $styleDiagnostics,
    $cmsAssetDiagnostics,
    $jsonDiagnostics,
    $javascriptDiagnostics,
    $composerDiagnostics,
    $skeletonAssetDiagnostics,
));

exit($exitCode);

function progress(string $message): void
{
    fwrite(STDERR, '[annabel diagnostics] ' . $message . PHP_EOL);
}

/**
 * @return list<string>
 */
function phpFiles(string $root): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file): bool {
                if (!$file->isDir()) {
                    return $file->getExtension() === 'php';
                }

                return !in_array($file->getFilename(), excludedDirectories(), true);
            },
        ),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * @return list<string>
 */
function excludedDirectories(): array
{
    return [
        '.git',
        'build',
        'coverage',
        'dist',
        'node_modules',
        'storage',
        'var',
        'vendor',
    ];
}

function lintPhpFile(string $root, string $file): ?string
{
    $output = [];
    $status = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

    if ($status === 0) {
        return null;
    }

    $message = implode(' ', array_filter($output, static fn (string $line): bool => $line !== ''));
    $lineNumber = 1;

    if (preg_match('/ on line (\d+)$/', $message, $matches) === 1) {
        $lineNumber = (int) $matches[1];
    }

    return sprintf('%s:%d:%s', relativePath($root, $file), $lineNumber, cleanPhpLintMessage($message));
}

function cleanPhpLintMessage(string $message): string
{
    $message = preg_replace('/ in .+ on line \d+$/', '', $message) ?? $message;
    $message = preg_replace('/^PHP Parse error:\s*/', '', $message) ?? $message;
    $message = preg_replace('/^Parse error:\s*/', '', $message) ?? $message;

    return trim($message);
}

/**
 * @param list<string> $phpFiles
 */
function checkPhpstanCoverage(string $root, array $phpFiles, bool $strict): int
{
    $coverage = phpstanCoverage($root);
    $diagnostics = 0;

    foreach ($phpFiles as $file) {
        if (!shouldRequirePhpstanCoverage($root, $file, $strict)) {
            continue;
        }

        if (isCoveredByPhpstan($file, $coverage['paths'], $coverage['excludePaths'])) {
            continue;
        }

        fwrite(STDOUT, sprintf(
            '%s:1:warning:No PHPStan config covers this PHP file',
            relativePath($root, $file),
        ) . PHP_EOL);
        $diagnostics++;
    }

    return $diagnostics;
}

function shouldRequirePhpstanCoverage(string $root, string $file, bool $strict): bool
{
    $relative = relativePath($root, $file);

    if (str_contains($relative, '/bootstrap/cache/')) {
        return false;
    }

    if (str_contains($relative, '/fixtures/')
        || str_contains($relative, '/cache/')
        || str_contains($relative, '/tests/cache/')
    ) {
        return false;
    }

    if ($relative === '.php-cs-fixer.dist.php') {
        return false;
    }

    if (str_ends_with($relative, '/phpstan-bootstrap.php')
        || str_ends_with($relative, '/phpstan-stubs.php')
        || $relative === 'tools/phpstan-bootstrap.php'
    ) {
        return false;
    }

    if (!$strict && str_contains($relative, '/config/')) {
        return false;
    }

    if (str_contains($relative, '/tests/') || str_contains($relative, '/test/')) {
        return false;
    }

    if (!$strict && (str_starts_with($relative, 'skeleton/annabel-skeleton/')
        || str_starts_with($relative, 'tools/'))
    ) {
        return false;
    }

    return true;
}

/**
 * @return array{paths: list<string>, excludePaths: list<string>}
 */
function phpstanCoverage(string $root): array
{
    $paths = [];
    $excludePaths = [];

    foreach (phpstanConfigFiles($root) as $config) {
        $directory = dirname($config);

        foreach (phpstanListValues($config, 'paths') as $path) {
            $paths[] = absoluteConfigPath($directory, $path);
        }

        foreach (phpstanListValues($config, 'excludePaths') as $path) {
            $excludePaths[] = absoluteConfigPath($directory, $path);
        }
    }

    sort($paths);
    sort($excludePaths);

    return [
        'paths' => $paths,
        'excludePaths' => $excludePaths,
    ];
}

/**
 * @return list<string>
 */
function phpstanListValues(string $config, string $section): array
{
    $values = [];
    $lines = file($config, FILE_IGNORE_NEW_LINES) ?: [];
    $inside = false;
    $baseIndent = null;

    foreach ($lines as $line) {
        if (preg_match('/^(\s*)' . preg_quote($section, '/') . ':\s*$/', $line, $matches) === 1) {
            $inside = true;
            $baseIndent = strlen($matches[1]);
            continue;
        }

        if (!$inside) {
            continue;
        }

        if (trim($line) === '') {
            continue;
        }

        $indent = strspn($line, ' ');
        if ($baseIndent !== null && $indent <= $baseIndent && str_ends_with(trim($line), ':')) {
            break;
        }

        if (preg_match('/^\s*-\s+(.+?)\s*$/', $line, $matches) === 1) {
            $values[] = trim($matches[1], '\'"');
        }
    }

    return $values;
}

function absoluteConfigPath(string $directory, string $path): string
{
    $path = str_replace('%rootDir%', $directory, $path);

    if (str_starts_with($path, '/')) {
        return normalizePath($path);
    }

    return normalizePath($directory . '/' . $path);
}

/**
 * @param list<string> $paths
 * @param list<string> $excludePaths
 */
function isCoveredByPhpstan(string $file, array $paths, array $excludePaths): bool
{
    $file = normalizePath($file);

    foreach ($excludePaths as $excludePath) {
        if (pathContains($excludePath, $file)) {
            return true;
        }
    }

    foreach ($paths as $path) {
        if (pathContains($path, $file)) {
            return true;
        }
    }

    return false;
}

function pathContains(string $path, string $file): bool
{
    $path = normalizePath($path);

    if (is_file($path)) {
        return $file === $path;
    }

    return $file === $path || str_starts_with($file, rtrim($path, '/') . '/');
}

function lintJsonFiles(string $root): int
{
    $diagnostics = 0;

    foreach (filesWithExtension($root, 'json') as $file) {
        json_decode((string) file_get_contents($file), true);

        if (json_last_error() === JSON_ERROR_NONE) {
            continue;
        }

        fwrite(STDOUT, sprintf(
            '%s:1:Invalid JSON: %s',
            relativePath($root, $file),
            json_last_error_msg(),
        ) . PHP_EOL);
        $diagnostics++;
    }

    return $diagnostics;
}

function lintJavaScriptFiles(string $root): int
{
    $diagnostics = 0;

    foreach (filesWithExtension($root, 'js') as $file) {
        $process = proc_open(
            [
                'node',
                '--check',
                $file,
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $root,
        );

        if (!is_resource($process)) {
            fwrite(STDOUT, relativePath($root, $file) . ':1:Unable to start JavaScript syntax check' . PHP_EOL);
            $diagnostics++;

            continue;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        if ($status === 0) {
            continue;
        }

        fwrite(STDOUT, sprintf(
            '%s:%d:%s',
            relativePath($root, $file),
            firstNodeErrorLine($stderr . "\n" . $stdout),
            firstUsefulBuildError($stderr . "\n" . $stdout) ?? 'Invalid JavaScript syntax',
        ) . PHP_EOL);
        $diagnostics++;
    }

    return $diagnostics;
}

function firstNodeErrorLine(string $output): int
{
    if (preg_match('/:(\d+)\s*$/m', $output, $matches) === 1) {
        return max(1, (int) $matches[1]);
    }

    return 1;
}

function validateComposerManifests(string $root): int
{
    $diagnostics = 0;

    foreach (filesNamed($root, 'composer.json') as $manifest) {
        $directory = dirname($manifest);
        $process = proc_open(
            [
                'composer',
                'validate',
                '--strict',
                '--no-check-lock',
                '--working-dir=' . $directory,
            ],
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $root,
        );

        if (!is_resource($process)) {
            fwrite(STDOUT, relativePath($root, $manifest) . ':1:Unable to start Composer manifest validation' . PHP_EOL);
            $diagnostics++;

            continue;
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        if ($status === 0) {
            continue;
        }

        fwrite(STDOUT, sprintf(
            '%s:1:%s',
            relativePath($root, $manifest),
            firstComposerValidationError($stdout . "\n" . $stderr) ?? 'Composer manifest is invalid',
        ) . PHP_EOL);
        $diagnostics++;
    }

    return $diagnostics;
}

function firstComposerValidationError(string $output): ?string
{
    foreach (explode("\n", $output) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, './composer.json is valid')) {
            continue;
        }

        if (str_starts_with($line, './composer.json')) {
            continue;
        }

        return $line;
    }

    return null;
}

/**
 * @return list<string>
 */
function filesWithExtension(string $root, string $extension): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file): bool {
                if (!$file->isDir()) {
                    return true;
                }

                return !in_array($file->getFilename(), excludedDirectories(), true);
            },
        ),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === $extension) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * @return list<string>
 */
function filesNamed(string $root, string $filename): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $file): bool {
                if (!$file->isDir()) {
                    return true;
                }

                return !in_array($file->getFilename(), excludedDirectories(), true);
            },
        ),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile() && $file->getFilename() === $filename) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

/**
 * @return array{ready: list<array{directory: string, config: string, binary: string}>, skipped: list<string>}
 */
function phpstanTargets(string $root): array
{
    $targets = [
        'ready' => [],
        'skipped' => [],
    ];
    $fallbackBinary = fallbackPhpstanBinary($root);

    foreach (phpstanConfigFiles($root) as $config) {
        $directory = dirname($config);
        $binary = $directory . '/vendor/bin/phpstan';

        if (!is_file($binary)) {
            $binary = $fallbackBinary;
        }

        if ($binary === null) {
            $targets['skipped'][] = relativePath($root, $directory);

            continue;
        }

        $targets['ready'][] = [
            'directory' => $directory,
            'config' => $config,
            'binary' => $binary,
        ];
    }

    return $targets;
}

function fallbackPhpstanBinary(string $root): ?string
{
    foreach ([
        $root . '/vendor/bin/phpstan',
        $root . '/applications/annabel-cms/vendor/bin/phpstan',
        $root . '/packages/framework/vendor/bin/phpstan',
    ] as $binary) {
        if (is_file($binary)) {
            return $binary;
        }
    }

    foreach (glob($root . '/packages/*/vendor/bin/phpstan') ?: [] as $binary) {
        if (is_file($binary)) {
            return $binary;
        }
    }

    return null;
}

/**
 * @return list<string>
 */
function phpstanConfigFiles(string $root): array
{
    $configs = [];

    foreach (filesNamed($root, 'phpstan.neon') as $file) {
        $configs[] = $file;
    }

    foreach (filesNamed($root, 'phpstan.neon.dist') as $file) {
        $configs[] = $file;
    }

    sort($configs);

    return $configs;
}

/**
 * @param list<array{directory: string, config: string, binary: string}> $targets
 * @return list<array{directory: string, config: string, binary: string}>
 */
function packagePhpstanTestTargets(string $root, array $targets): array
{
    $packageRoot = normalizePath($root . '/packages');
    $testTargets = [];

    foreach ($targets as $target) {
        $directory = normalizePath($target['directory']);

        if (!str_starts_with($directory, $packageRoot . '/')) {
            continue;
        }

        if (!is_dir($directory . '/tests')) {
            continue;
        }

        if (phpstanConfigCoversDirectory($target['config'], $directory . '/tests')) {
            continue;
        }

        $testTargets[] = $target;
    }

    return $testTargets;
}

function phpstanConfigCoversDirectory(string $config, string $directory): bool
{
    $directory = normalizePath($directory);
    $configDirectory = dirname($config);

    foreach (phpstanListValues($config, 'paths') as $path) {
        $coveredPath = normalizePath(absoluteConfigPath($configDirectory, $path));

        if ($coveredPath === $directory || str_starts_with($directory, $coveredPath . '/')) {
            return true;
        }
    }

    return false;
}

/**
 * @param array{directory: string, config: string, binary: string} $target
 * @param list<string> $paths
 */
function runPhpstan(string $root, array $target, array $paths = []): int
{
    $command = [
        $target['binary'],
        'analyse',
        '--configuration',
        $target['config'],
        '--error-format=raw',
        '--no-progress',
        '--memory-limit=1G',
    ];

    foreach ($paths as $path) {
        $command[] = $path;
    }

    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $target['directory'],
    );

    if (!is_resource($process)) {
        fwrite(STDERR, relativePath($root, $target['directory']) . ': unable to start PHPStan' . PHP_EOL);

        return 1;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    foreach (explode("\n", trim($stdout . "\n" . $stderr)) as $line) {
        emitPhpstanLine($root, $target['directory'], $target['config'], trim($line));
    }

    return proc_close($process);
}

function runPhpCsFixer(string $root): int
{
    $binary = $root . '/vendor/bin/php-cs-fixer';

    if (!is_file($binary)) {
        fwrite(STDERR, 'Annabel diagnostics: PHP-CS-Fixer skipped (missing vendor/bin/php-cs-fixer).' . PHP_EOL);

        return 0;
    }

    $process = proc_open(
        [
            $binary,
            'fix',
            '--dry-run',
            '--format=json',
            '--using-cache=no',
            '--diff',
            '--verbose',
        ],
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
    );

    if (!is_resource($process)) {
        fwrite(STDOUT, 'composer.json:1:Unable to start PHP-CS-Fixer' . PHP_EOL);

        return 1;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    $jsonOffset = strpos($stdout, '{"about"');
    if ($jsonOffset === false) {
        foreach (explode("\n", trim($stdout . "\n" . $stderr)) as $line) {
            $line = trim($line);

            if ($line !== '') {
                fwrite(STDERR, '[php-cs-fixer] ' . $line . PHP_EOL);
            }
        }

        return 0;
    }

    $report = json_decode(substr($stdout, $jsonOffset), true);
    if (!is_array($report) || !isset($report['files']) || !is_array($report['files'])) {
        fwrite(STDOUT, 'composer.json:1:Unable to parse PHP-CS-Fixer diagnostics' . PHP_EOL);

        return 1;
    }

    foreach ($report['files'] as $file) {
        if (!is_array($file) || !isset($file['name']) || !is_string($file['name'])) {
            continue;
        }

        $rules = isset($file['appliedFixers']) && is_array($file['appliedFixers'])
            ? implode(', ', array_filter($file['appliedFixers'], 'is_string'))
            : '';
        $line = isset($file['diff']) && is_string($file['diff']) ? firstDiffLine($file['diff']) : 1;
        $message = $rules === ''
            ? 'PHP-CS-Fixer would change this file'
            : 'PHP-CS-Fixer ' . $rules . ' would change this file';

        fwrite(STDOUT, normalizeRelativePath($file['name']) . ':' . $line . ':' . $message . PHP_EOL);
    }

    return count($report['files']);
}

function firstDiffLine(string $diff): int
{
    if (preg_match('/^@@ -(\d+)/m', $diff, $matches) === 1) {
        return max(1, (int) $matches[1]);
    }

    return 1;
}

function runCmsAssetBuilds(string $root): int
{
    $cmsDirectory = $root . '/applications/annabel-cms';

    if (!is_file($cmsDirectory . '/node_modules/.bin/vite')) {
        fwrite(STDERR, 'Annabel diagnostics: CMS asset builds skipped (missing applications/annabel-cms/node_modules/.bin/vite).' . PHP_EOL);

        return 0;
    }

    $failures = 0;
    $targets = [
        [
            'name' => 'CMS admin assets',
            'script' => 'build:admin',
            'environment' => [
                'ANNABEL_CMS_ADMIN_ASSETS_ROOT' => '/tmp/annabel-vscode-diagnostics-admin-assets',
            ],
        ],
        [
            'name' => 'CMS setup assets',
            'script' => 'build:setup',
            'environment' => [
                'ANNABEL_CMS_SETUP_ASSETS_ROOT' => '/tmp/annabel-vscode-diagnostics-setup-assets',
            ],
        ],
    ];

    foreach ($targets as $target) {
        $status = runCmsAssetBuild($root, $cmsDirectory, $target['name'], $target['script'], $target['environment']);

        if ($status !== 0) {
            $failures++;
        }
    }

    return $failures;
}

function runSkeletonAssetBuild(string $root): int
{
    $directory = $root . '/skeleton/annabel-skeleton';

    if (!is_file($directory . '/node_modules/.bin/vite')) {
        fwrite(STDERR, 'Annabel diagnostics: skeleton asset build skipped (missing skeleton/annabel-skeleton/node_modules/.bin/vite).' . PHP_EOL);

        return 0;
    }

    $status = runNpmBuild(
        $root,
        $directory,
        'skeleton assets',
        'build',
        [
            'ANNABEL_SKELETON_ASSETS_ROOT' => '/tmp/annabel-vscode-diagnostics-skeleton-assets',
        ],
    );

    return $status === 0 ? 0 : 1;
}

/**
 * @param array<string, string> $environment
 */
function runCmsAssetBuild(string $root, string $cmsDirectory, string $name, string $script, array $environment): int
{
    return runNpmBuild($root, $cmsDirectory, $name, $script, $environment);
}

/**
 * @param array<string, string> $environment
 */
function runNpmBuild(string $root, string $directory, string $name, string $script, array $environment): int
{
    $process = proc_open(
        [
            'npm',
            'run',
            $script,
        ],
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $directory,
        array_merge(getenv(), $environment),
    );

    if (!is_resource($process)) {
        fwrite(STDOUT, relativePath($root, $directory . '/package.json') . ':1:Unable to start ' . $name . ' build' . PHP_EOL);

        return 1;
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $status = proc_close($process);

    if ($status === 0) {
        return 0;
    }

    emitAssetBuildDiagnostic($root, $directory, $name, $stdout . "\n" . $stderr);

    return $status;
}

function emitAssetBuildDiagnostic(string $root, string $directory, string $name, string $output): void
{
    foreach (explode("\n", $output) as $line) {
        $line = trim($line);

        if (preg_match('/^(.+?):(\d+):(\d+):\s*(.+)$/', $line, $matches) === 1) {
            $file = str_starts_with($matches[1], '/')
                ? relativePath($root, $matches[1])
                : relativePath($root, $directory . '/' . $matches[1]);

            fwrite(STDOUT, sprintf('%s:%d:%s: %s', $file, (int) $matches[2], $name, trim($matches[4])) . PHP_EOL);

            return;
        }
    }

    $message = firstUsefulBuildError($output) ?? $name . ' build failed';
    fwrite(STDOUT, relativePath($root, $directory . '/package.json') . ':1:' . $name . ': ' . $message . PHP_EOL);
}

function firstUsefulBuildError(string $output): ?string
{
    foreach (explode("\n", $output) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '>') || str_starts_with($line, 'Container ')) {
            continue;
        }

        if (str_starts_with($line, 'Error: ')) {
            return substr($line, strlen('Error: '));
        }
    }

    return null;
}

function emitPhpstanLine(string $root, string $directory, string $config, string $line): void
{
    if ($line === '' || str_starts_with($line, '[')) {
        return;
    }

    if (preg_match('/^(.+?):(\d+):(.+)$/', $line, $matches) !== 1) {
        fwrite(STDOUT, sprintf('%s:1:%s', relativePath($root, $config), $line) . PHP_EOL);

        return;
    }

    $file = $matches[1];
    $path = str_starts_with($file, '/')
        ? relativePath($root, $file)
        : relativePath($root, $directory . '/' . $file);

    fwrite(STDOUT, sprintf('%s:%d:%s', $path, (int) $matches[2], trim($matches[3])) . PHP_EOL);
}

function relativePath(string $root, string $path): string
{
    $root = str_replace('\\', '/', realpath($root) ?: $root);
    $path = str_replace('\\', '/', realpath($path) ?: $path);

    if (str_starts_with($path, $root . '/')) {
        return substr($path, strlen($root) + 1);
    }

    return $path;
}

function normalizeRelativePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', realpath($path) ?: $path);
}
