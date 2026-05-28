<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->ignoreErrorsOnPackages(
        [
            'cycle/schema-builder',
            'symfony/finder',
        ],
        [ErrorType::SHADOW_DEPENDENCY],
    )
    ->ignoreErrorsOnExtension('ext-zend-opcache', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackages(
        [
            'cycle/entity-behavior',
            'cycle/migrations',
            'cycle/schema-migrations-generator',
            'symfony/console',
        ],
        [ErrorType::DEV_DEPENDENCY_IN_PROD],
    )
;
