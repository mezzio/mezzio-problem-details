<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../src',
        __DIR__ . '/../../test',
    ])
    ->withPreparedSets(
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        phpunitCodeQuality: true,
    )
    ->withPhpSets(php82: true)
    ->withSkip([
        YieldDataProviderRector::class,
        CreateStubOverCreateMockArgRector::class,
        ExpressionCreateMockToCreateStubRector::class,
    ]);
