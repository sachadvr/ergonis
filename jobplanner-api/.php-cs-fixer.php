<?php

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__.'/src', __DIR__.'/tests']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,

        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],

        'doctrine_annotation_array_assignment' => true,
        'doctrine_annotation_braces' => true,
        'doctrine_annotation_indentation' => true,
        'doctrine_annotation_spaces' => true,

        'phpdoc_order' => true,
        'phpdoc_types_order' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'general_phpdoc_annotation_remove' => [
            'annotations' => ['author', 'package'],
        ],

        'no_unused_imports' => true,
        'no_superfluous_elseif' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,

        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,

        'method_chaining_indentation' => true,
        'multiline_comment_opening_closing' => true,
        'align_multiline_comment' => true,

        'linebreak_after_opening_tag' => true,

        'nullable_type_declaration_for_default_null_value' => true,
    ])
    ->setCacheFile(__DIR__.'/.php_cs.cache')
    ->setFinder($finder);
