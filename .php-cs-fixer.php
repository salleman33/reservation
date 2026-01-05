<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->ignoreVCSIgnored(true)
    ->name('*.php');

$config = new Config();

$rules = [
    '@PER-CS' => true, // Latest PER rules.
];

return $config
    ->setRules($rules)
    ->setFinder($finder)
    ->setUsingCache(false);
;
