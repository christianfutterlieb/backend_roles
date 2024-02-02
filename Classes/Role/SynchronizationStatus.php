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
    public const NONE = 0;
    public const NOK = 1;
    public const OK = 2;

    protected int $status;

    public function __construct(int $status)
    {
        if (!in_array($status, [self::NONE, self::NOK, self::OK])) {
            throw new \InvalidArgumentException('Invalid status: ' . $status);
        }
        $this->status = $status;
    }

    public function isAvailable(): bool
    {
        return $this->status !== self::NONE;
    }

    public function isOutOfSync(): bool
    {
        return $this->status === self::NOK;
    }

    public function isSynced(): bool
    {
        return $this->status === self::OK;
    }
}
