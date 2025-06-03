<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\EventListener;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Imaging\IconHandler;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

/**
 * ModifyRecordOverlayIconIdentifierEventListener
 */
final class ModifyRecordOverlayIconIdentifierEventListener
{
    private readonly bool $enabled;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        private readonly IconHandler $iconHandler
    ) {
        $this->enabled = (bool)($extensionConfiguration->get('backend_roles')['showSynchronizationStatus'] ?? true);
    }

    public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $originalOverlayIconIdentifier = $event->getOverlayIconIdentifier();
        $overlayIconIdentifier = $this->iconHandler->postOverlayPriorityLookup(
            $event->getTable(),
            $event->getRow(),
            $event->getStatus(),
            $originalOverlayIconIdentifier
        );

        if ($originalOverlayIconIdentifier !== $overlayIconIdentifier) {
            $event->setOverlayIconIdentifier($overlayIconIdentifier);
        }
    }
}
