<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS3x0' => true,
        '@PHP8x4Migration' => true,
        'declare_strict_types' => true,
        'final_class' => true,
        'global_namespace_import' => ['import_classes' => true, 'import_functions' => false, 'import_constants' => false],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache');
