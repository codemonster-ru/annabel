<?php

declare(strict_types=1);

/**
 * Checks that public Composer manifests and package splitting are releasable.
 */

$root = dirname(__DIR__);
$violations = [];

$publicManifests = array_merge(
    packageComposerFiles($root),
    [
        $root . '/skeleton/annabel-skeleton/composer.json',
        $root . '/applications/annabel-cms/composer.json',
    ],
);

foreach ($publicManifests as $composerFile) {
    inspectComposerManifest($root, $composerFile, $violations);
    inspectChangelog($root, $composerFile, $violations);
}

inspectSplitWorkflow($root, $violations);
inspectCmsAssets($root, $violations);

if ($violations !== []) {
    fwrite(STDERR, "Release packaging violations:\n\n");

    foreach ($violations as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }

    exit(1);
}

printf("Release packaging is valid (%d public manifests checked).\n", count($publicManifests));

/**
 * @return list<string>
 */
function packageComposerFiles(string $root): array
{
    $files = glob($root . '/packages/*/composer.json') ?: [];
    sort($files);

    return $files;
}

/**
 * @param list<string> $violations
 */
function inspectComposerManifest(string $root, string $composerFile, array &$violations): void
{
    $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
    $relativeFile = relativePath($root, $composerFile);

    foreach (['require', 'conflict', 'replace', 'provide'] as $section) {
        if (!isset($composer[$section]) || !is_array($composer[$section])) {
            continue;
        }

        foreach ($composer[$section] as $package => $constraint) {
            if (!is_string($constraint)) {
                continue;
            }

            if (str_contains($constraint, '@dev')) {
                $violations[] = "{$relativeFile}: {$section}.{$package} must not require dev stability in a public release constraint";
            }
        }
    }

    if (str_contains((string) ($composer['version'] ?? ''), 'dev')) {
        $violations[] = "{$relativeFile}: public release manifests must not pin a dev version";
    }

    foreach (($composer['repositories'] ?? []) as $index => $repository) {
        if (!is_array($repository) || ($repository['type'] ?? null) !== 'path') {
            continue;
        }

        if (($repository['canonical'] ?? null) !== false) {
            $violations[] = "{$relativeFile}: repositories.{$index}.canonical must be false for local package development";
        }
    }
}

/**
 * @param list<string> $violations
 */
function inspectChangelog(string $root, string $composerFile, array &$violations): void
{
    $packageDirectory = dirname($composerFile);
    $changelogFile = $packageDirectory . '/CHANGELOG.md';

    if (!is_file($changelogFile)) {
        return;
    }

    $composer = json_decode((string) file_get_contents($composerFile), true, flags: JSON_THROW_ON_ERROR);
    $relativeFile = relativePath($root, $changelogFile);
    $contents = (string) file_get_contents($changelogFile);

    if (!preg_match('/^##\s+\[([0-9]+\.[0-9]+\.[0-9]+)\]\s+-\s+([0-9]{4}-[0-9]{2}-[0-9]{2})\s*$/m', $contents, $matches)) {
        $violations[] = "{$relativeFile}: first release heading must use ## [x.y.z] - YYYY-MM-DD so split releases can extract notes";

        return;
    }

    $releaseVersion = $matches[1];
    $branchAlias = $composer['extra']['branch-alias']['dev-main'] ?? null;

    if (!is_string($branchAlias) || !preg_match('/^([0-9]+\.[0-9]+)\.x-dev$/', $branchAlias, $aliasMatches)) {
        return;
    }

    if (!str_starts_with($releaseVersion, $aliasMatches[1] . '.')) {
        $violations[] = "{$relativeFile}: release {$releaseVersion} does not match branch alias {$branchAlias}";
    }
}

/**
 * @param list<string> $violations
 */
function inspectCmsAssets(string $root, array &$violations): void
{
    inspectCmsAssetBundle(
        $root,
        'admin',
        'applications/annabel-cms/public/admin/assets',
        'resources/js/main.js',
        $violations,
    );

    inspectCmsAssetBundle(
        $root,
        'setup',
        'applications/annabel-cms/public/setup/assets',
        'resources/js/main.js',
        $violations,
    );
}

/**
 * @param list<string> $violations
 */
function inspectCmsAssetBundle(
    string $root,
    string $name,
    string $relativeAssetsDirectory,
    string $entrypoint,
    array &$violations,
): void {
    $assetsDirectory = $root . '/' . $relativeAssetsDirectory;
    $manifestPath = $relativeAssetsDirectory . '/.vite/manifest.json';
    $manifestFile = $root . '/' . $manifestPath;

    if (!is_file($manifestFile)) {
        $violations[] = "{$manifestPath}: compiled {$name} assets must be shipped";

        return;
    }

    $manifest = json_decode((string) file_get_contents($manifestFile), true);
    $entry = is_array($manifest) ? ($manifest[$entrypoint] ?? null) : null;

    if (!is_array($entry) || !is_string($entry['file'] ?? null)) {
        $violations[] = "{$manifestPath}: {$name} entrypoint is missing";

        return;
    }

    $stylesheets = $entry['css'] ?? [];

    if (!is_array($stylesheets)) {
        $violations[] = "{$manifestPath}: {$name} stylesheets must be an array";

        return;
    }

    $files = [$entry['file']];

    foreach ($stylesheets as $stylesheet) {
        if (is_string($stylesheet)) {
            $files[] = $stylesheet;
        }
    }

    foreach ($files as $file) {
        if (!is_file($assetsDirectory . '/' . ltrim($file, '/'))) {
            $violations[] = "{$relativeAssetsDirectory}/{$file}: manifest target is missing";
        }
    }
}

/**
 * @param list<string> $violations
 */
function inspectSplitWorkflow(string $root, array &$violations): void
{
    $workflowFile = $root . '/.github/workflows/split.yml';

    if (!is_file($workflowFile)) {
        $violations[] = '.github/workflows/split.yml: split workflow is missing';

        return;
    }

    $contents = (string) file_get_contents($workflowFile);
    $packageIds = splitPackageIds($contents);

    foreach ($packageIds as $packageId) {
        $tagPattern = "- '{$packageId}/v*.*.*'";

        if (!str_contains($contents, $tagPattern)) {
            $violations[] = ".github/workflows/split.yml: package {$packageId} is missing tag trigger {$tagPattern}";
        }
    }
}

/**
 * @return list<string>
 */
function splitPackageIds(string $workflowContents): array
{
    preg_match_all('/^\s+- id:\s*([a-z0-9-]+)\s*$/m', $workflowContents, $matches);

    $packageIds = $matches[1];
    sort($packageIds);

    return array_values(array_unique($packageIds));
}

function relativePath(string $root, string $path): string
{
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
}
