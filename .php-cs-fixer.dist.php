<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/packages',
        __DIR__ . '/skeleton/annabel-skeleton',
        __DIR__ . '/tools',
    ])
    ->exclude([
        'vendor',
        'var',
        'storage',
        'cache',
    ])
    ->notPath([
        'packages/framework/tests/Bootstrap/cache/packages.php',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
        'blank_line_after_opening_tag' => true,
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'cast_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => false,
        'no_extra_blank_lines' => true,
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'single_quote' => true,
        'trailing_comma_in_multiline' => [
            'after_heredoc' => false,
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
    ])
    ->setFinder($finder);
