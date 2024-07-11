<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPreparedSets(deadCode: true, codeQuality: true)
    ->withPhpSets()
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPaths([
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ]);
