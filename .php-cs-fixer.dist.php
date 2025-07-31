<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['venders','vendor']);

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'no_extra_blank_lines' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        // Add more rules as you need
    ])
    ->setFinder($finder);
