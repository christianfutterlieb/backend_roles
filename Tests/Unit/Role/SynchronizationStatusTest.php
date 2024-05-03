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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * SynchronizationStatusTest
 */
class SynchronizationStatusTest extends UnitTestCase
{
    /**
     * @test
     */
    public function constructorAcceptsKnownStatusValue(): void
    {
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::NONE)
        );
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::NOK)
        );
        self::assertInstanceOf(
            SynchronizationStatus::class,
            new SynchronizationStatus(SynchronizationStatus::OK)
        );
    }

    /**
     * @test
     * @dataProvider unknownConstructorStatesDataProvider
     */
    public function constructorFailsWithUnknownStatusValue(int $status): void
    {
        self::expectException(\InvalidArgumentException::class);
        new SynchronizationStatus($status);
    }

    /**
     * @test
     */
    public function statusConstantIsInterpretedCorrectly(): void
    {
        // NOT_SYNCED
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NONE))->isAvailable()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NONE))->isOutOfSync()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NONE))->isSynced()
        );

        // OUT_OF_SYNC
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::NOK))->isAvailable()
        );
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::NOK))->isOutOfSync()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::NOK))->isSynced()
        );

        // SYNC_OK
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::OK))->isAvailable()
        );
        self::assertFalse(
            (new SynchronizationStatus(SynchronizationStatus::OK))->isOutOfSync()
        );
        self::assertTrue(
            (new SynchronizationStatus(SynchronizationStatus::OK))->isSynced()
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
