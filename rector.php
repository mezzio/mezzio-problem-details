<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\YieldDataProviderRector;
use Rector\PHPUnit\PHPUnit120\Rector\CallLike\CreateStubOverCreateMockArgRector;
use Rector\PHPUnit\PHPUnit120\Rector\ClassMethod\ExpressionCreateMockToCreateStubRector;

return RectorConfig::configure()
    ->withPhpSets(php82: true)
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withPreparedSets(
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        phpunitCodeQuality: true,
    )
    ->withSkip([
        YieldDataProviderRector::class,
        CreateStubOverCreateMockArgRector::class,
        ExpressionCreateMockToCreateStubRector::class,
    ]);
