<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withImportNames(
        importShortClasses: false,
    )
    ->withPhpVersion(PhpVersion::PHP_81)
    ->withCache(
        '.Build/.cache/rector',
        FileCacheStorage::class
    )
    ->withPaths([
        __DIR__ . '/../..',
    ])
    ->withSkip([
        __DIR__ . '/../../.Build',
    ])
    ->withPhpSets(
        php83: true,
    )
    ->withPreparedSets(
        codeQuality: true,
        deadCode: true,
        earlyReturn: true,
        instanceOf: true,
        strictBooleans: true,
        typeDeclarations: true,
    );
