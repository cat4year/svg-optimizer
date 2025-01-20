<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@PER-CS2.0' => true,
        '@PHP84Migration' => true,
        'octal_notation' => false,
        'declare_strict_types' => true,
        'lambda_not_used_import' => true,
        'logical_operators' => true,
        'final_class' => true,
        'final_internal_class' => true,
        'mb_str_functions' => true,
        'align_multiline_comment' => true,
        'fully_qualified_strict_types' => true,
        'method_chaining_indentation' => true,
        'modernize_strpos' => true,
        'no_empty_comment' => true,
        'not_operator_with_space' => false,
        'simplified_if_return' => true,
        'strict_comparison' => true,
        'trim_array_spaces' => true,
        'use_arrow_functions' => true,
        'void_return' => true,
        'phpdoc_align' => [
            'align' => 'left',
            'spacing' => [
                'param' => 1
            ]
        ],
        'static_lambda' => true,
        'no_superfluous_phpdoc_tags' => true,
        'multiline_comment_opening_closing' => true,
        'phpdoc_single_line_var_spacing' => true,
        //situationally rules
        'single_line_empty_body' => false,
        'ordered_traits' => true,
        'protected_to_private' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'is_null' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true
        ],
        'phpdoc_line_span' => [
            'property' => 'single'
        ],
        'phpdoc_separation' => [
            'groups' => [
                ['deprecated', 'link', 'see', 'since'],
                ['author', 'copyright', 'license'],
                ['category', 'package', 'subpackage'],
                ['phpstan-type', 'phpstan-import-type', 'property', 'property-read', 'property-write', 'mixin'],
                ['param', 'return'],
                ['throws']
            ]
        ]
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
