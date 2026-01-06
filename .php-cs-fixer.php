<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        '.git/',
        'node_modules/',
        'tools/',
        'vendor/',
    ])
;

return (new PhpCsFixer\Config())
    ->setUnsupportedPhpVersionAllowed(true) // allow upcoming PHP versions
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PER-CS3x0' => true,
        '@PHP8x4Migration' => true,
        'fully_qualified_strict_types' => ['import_symbols' => true],
        'ordered_imports' => ['imports_order' => ['class', 'const', 'function']],
        'no_unused_imports' => true,
        'heredoc_indentation' => false, // This rule is mandatory due to a bug in `xgettext`, see https://savannah.gnu.org/bugs/?func=detailitem&item_id=62158
        'new_expression_parentheses' => false, // breaks compatibility with PHP < 8.4
        'phpdoc_scalar' => true, // Normalize scalar types identifiers in PHPDoc
        'phpdoc_types' => true, // Fixes types case in PHPDoc
    ])
    ->setFinder($finder)
;