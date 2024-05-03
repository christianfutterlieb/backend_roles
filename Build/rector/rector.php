<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withImportNames(
        true, // importNames (default)
        true, // importDocBlockNames (default)
        false // importShortClasses
    )
    ->withPhpVersion(PhpVersion::PHP_74)
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
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        Setlist::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        SetList::TYPE_DECLARATION,
    ]);
