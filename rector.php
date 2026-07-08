<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src'])
    ->withPhpSets(php84: true)
    ->withSets([LevelSetList::UP_TO_PHP_84])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class,
    ]);
