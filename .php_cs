<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(array(
        __DIR__.'/Pomm/',
        __DIR__.'/tests/',
    ))
;

return Symfony\CS\Config\Config::create()
    ->fixers(array(
        'braces',
        'controls_spaces',
        'elseif',
        'eof_ending',
        'extra_empty_lines',
        'include',
        'indentation',
        'linefeed',
        'php_closing_tag',
        'return',
        'short_tag',
        'trailing_spaces',
        'unused_use',
        'visibility',
    ))
    ->finder($finder)
;

