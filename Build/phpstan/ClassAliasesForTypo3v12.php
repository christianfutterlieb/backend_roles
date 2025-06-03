<?php

declare(strict_types=1);

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

/*
 * Re-build the interface of ModifyRecordOverlayIconIdentifierEvent to prevent
 * phpstan errors.
 * This construct can be removed as soon as support for TYPO3 <v13 is dropped.
 */
if (!class_exists(ModifyRecordOverlayIconIdentifierEvent::class)) {
    class AliasModifyRecordOverlayIconIdentifierEvent
    {
        public function setOverlayIconIdentifier(string $overlayIconIdentifier): void {}

        public function getOverlayIconIdentifier(): string
        {
            return '';
        }

        public function getTable(): string
        {
            return '';
        }

        public function getRow(): array
        {
            return [];
        }

        public function getStatus(): array
        {
            return [];
        }
    }
    class_alias(AliasModifyRecordOverlayIconIdentifierEvent::class, ModifyRecordOverlayIconIdentifierEvent::class);
}
