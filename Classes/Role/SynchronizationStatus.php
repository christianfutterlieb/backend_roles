<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Role;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * SynchronizationStatus
 */
final class SynchronizationStatus
{
    public const NOT_SYNCED = 0;
    public const OUT_OF_SYNC = 1;
    public const SYNC_OK = 2;

    public function __construct(public readonly int $status)
    {
        if (!in_array($status, [self::NOT_SYNCED, self::OUT_OF_SYNC, self::SYNC_OK])) {
            throw new \InvalidArgumentException('Invalid status: ' . $status);
        }
    }

    public function isSynced(): bool
    {
        return $this->status !== self::NOT_SYNCED;
    }

    public function isOutOfSync(): bool
    {
        return $this->status === self::OUT_OF_SYNC;
    }

    public function isSyncOk(): bool
    {
        return $this->status === self::SYNC_OK;
    }
}
