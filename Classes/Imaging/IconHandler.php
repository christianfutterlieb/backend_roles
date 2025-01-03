<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Imaging;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\SynchronizationStatus;
use AawTeam\BackendRoles\Role\SynchronizationStatusFactoryInterface;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * IconHandler
 */
final class IconHandler
{
    public function __construct(
        protected SynchronizationStatusFactoryInterface $synchronizationStatusFactory
    ) {}

    /**
     * Implementation of IconFactory hook:
     *
     *   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Imaging\IconFactory::class]['overrideIconOverlay']
     *
     * @param string[] $row
     * @param array<string, bool> $status
     */
    public function postOverlayPriorityLookup(string $table, array $row, array $status, string $iconName): string
    {
        if ($table !== 'be_groups') {
            return $iconName;
        }
        if (!array_key_exists('uid', $row) || !MathUtility::canBeInterpretedAsInteger($row['uid'])) {
            return $iconName;
        }
        // Do not override an existing overlay (in the case of be_groups records, this would be only the
        // 'hidden' overlay [or anything else from other hooks])
        if ($iconName !== '') {
            return $iconName;
        }

        // Note: this try/catch wrap for SynchronizationStatusFactoryInterface::createFromBackendGroupUid()
        //       can be removed when dropping support for TYPO3 <=v12.4
        try {
            $syncStatus = $this->synchronizationStatusFactory->createFromBackendGroupUid((int)$row['uid']);
        } catch (\RuntimeException $runtimeException) {
            // This exception can happen because of a bug in TYPO3, which has been fixed only for >=v12.4.9
            // See: https://forge.typo3.org/issues/102514
            if ($runtimeException->getCode() === 1708168047) {
                return '';
            }
            throw $runtimeException;
        }

        // Return incoming value ($iconName) when null was returned by mapping
        return $this->mapSynchronizationStatusToIconOverlayName($syncStatus) ?? $iconName;
    }

    /**
     * @todo register our own icons
     */
    protected function mapSynchronizationStatusToIconOverlayName(SynchronizationStatus $syncStatus): ?string
    {
        if ($syncStatus->isOutOfSync()) {
            return 'overlay-warning';
        }
        if ($syncStatus->isSynced()) {
            return 'overlay-approved';
        }

        // Default: return null
        return null;
    }
}
