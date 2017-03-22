<?php

$finder = PhpCsFixer\Finder::create()
    ->in('src')
    ->in('tests')
    ->notName('CodeNode.php')
    ->notName('StakxLogger.php')
    ->notName('TextExtension.php')
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@Symfony' => true,
        'array_syntax' => array('syntax' => 'long'),
        'concat_space' => array('spacing' => 'one'),
        'elseif' => false,
        'header_comment' => array(
            'header' => "@copyright 2017 Vladimir Jimenez\n@license   https://github.com/allejo/stakx/blob/master/LICENSE.md MIT",
            'commentType' => 'PHPDoc',
            'location' => 'after_open',
            'separate' => 'both',
        ),
        'no_short_echo_tag' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_imports' => array(
            'sortAlgorithm' => 'alpha',
            'importsOrder' => array(
                'const',
                'class',
                'function',
            ),
        ),
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'php_unit_fqcn_annotation' => false,
        'ternary_to_null_coalescing' => true
    ))
    ->setFinder($finder)
;
