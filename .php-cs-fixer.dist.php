<?php

declare(strict_types=1);

use Sirix\CsFixerConfig\ConfigBuilder;

return ConfigBuilder::create()
    ->inDir(__DIR__.'/src')
    ->inDir(__DIR__ . '/test')
	->inDir(__DIR__ . '/config')
    ->setRules([
        '@PHP8x1Migration' => true,
        'Gordinskiy/line_length_limit' => ['max_length' => 140],
        'php_unit_test_class_requires_covers' => false,
        'php_unit_internal_class' => false,
    ])
    ->getConfig()
;
