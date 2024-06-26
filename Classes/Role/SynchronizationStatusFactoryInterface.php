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
 * SynchronizationStatusFactoryInterface
 */
interface SynchronizationStatusFactoryInterface
{
    public function create(int $status): SynchronizationStatus;

    public function createFromBackendGroupUid(int $backendGroupuid): SynchronizationStatus;

    /**
     * @param string[] $backendGroupRecord
     */
    public function createFromBackendGroupRecord(array $backendGroupRecord): SynchronizationStatus;
}
