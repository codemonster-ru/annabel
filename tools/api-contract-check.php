<?php

declare(strict_types=1);

/**
 * Verifies that every source symbol is classified as public API or internal
 * implementation detail, and that public API does not reference internal
 * classes from another package.
 */

require __DIR__ . '/api-support.php';

$root = dirname(__DIR__);
$packages = annabelApiPackageDirectories($root);
$contracts = annabelApiContracts();
$symbols = [];
$violations = [];

foreach ($packages as $packageName => $packageDirectory) {
    if (!isset($contracts[$packageName])) {
        $violations[] = "{$packageName}: missing API contract entry";
        continue;
    }

    foreach (annabelApiPhpFiles($packageDirectory . '/src') as $file) {
        foreach (annabelApiDeclaredSymbols($file) as $symbol) {
            $classification = annabelApiClassify(
                $packageName,
                annabelApiRelativePath($packageDirectory, $file),
                $contracts[$packageName],
            );

            if ($classification === null) {
                $violations[] = sprintf(
                    '%s: %s is not classified as api or internal',
                    annabelApiRelativePath($root, $file),
                    $symbol,
                );
                continue;
            }

            $symbols[$symbol] = [
                'file' => $file,
                'package' => $packageName,
                'classification' => $classification,
            ];
        }
    }
}

foreach ($symbols as $symbol => $metadata) {
    if ($metadata['classification'] !== 'api') {
        continue;
    }

    foreach (annabelApiReferencedNames($metadata['file']) as [$reference, $line]) {
        $reference = ltrim($reference, '\\');
        $target = $symbols[$reference] ?? null;

        if ($target === null || $target['package'] === $metadata['package']) {
            continue;
        }

        if ($target['classification'] === 'internal') {
            $violations[] = sprintf(
                '%s:%d: public API %s references internal %s from %s',
                annabelApiRelativePath($root, $metadata['file']),
                $line,
                $symbol,
                $reference,
                $target['package'],
            );
        }
    }
}

if ($violations !== []) {
    fwrite(STDERR, "API contract violations:\n\n");

    foreach (array_unique($violations) as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }

    exit(1);
}

printf("API contracts are valid (%d packages, %d symbols).\n", count($packages), count($symbols));
