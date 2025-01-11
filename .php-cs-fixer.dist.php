<?php

declare(strict_types=1);

use Sirix\CsFixerConfig\ConfigBuilder;

return ConfigBuilder::create()
    ->inDir(__DIR__.'/src')
    ->inDir(__DIR__ . '/test')
	->inDir(__DIR__ . '/config')
    ->setRules([
        'phpdoc_to_comment' => false
    ])
    ->getConfig()
;
