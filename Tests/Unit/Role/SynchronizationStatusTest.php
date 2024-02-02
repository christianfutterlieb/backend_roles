<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Tests\Unit\Role;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\SynchronizationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * SynchronizationStatusTest
 */
class SynchronizationStatusTest extends UnitTestCase
{
    #[Test]
    public function constructorAcceptsKnownStatusValue(): void
    {
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::NOT_SYNCED)
        );
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::OUT_OF_SYNC)
        );
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::SYNC_OK)
        );
    }

    #[Test]
    #[DataProvider('unknownConstructorStatesDataProvider')]
    public function constructorFailsWithUnknownStatusValue(int $status): void
    {
        self::expectException(\InvalidArgumentException::class);
        new SynchronizationStatus($status);
    }

    #[Test]
    public function statusConstantIsInterpretedCorrectly(): void
    {
        // NOT_SYNCED
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NOT_SYNCED))->isSynced()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NOT_SYNCED))->isOutOfSync()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NOT_SYNCED))->isSyncOk()
        );

        // OUT_OF_SYNC
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::OUT_OF_SYNC))->isSynced()
        );
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::OUT_OF_SYNC))->isOutOfSync()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::OUT_OF_SYNC))->isSyncOk()
        );

        // SYNC_OK
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::SYNC_OK))->isSynced()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::SYNC_OK))->isOutOfSync()
        );
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::SYNC_OK))->isSyncOk()
        );
    }

    /**
     * @return mixed[]
     */
    public static function unknownConstructorStatesDataProvider(): array
    {
        return [
            'negative-status' => [-2],
            'one-below-known' => [-1],
            'one-above-known' => [3],
            'positive-status' => [4],
        ];
    }
}
