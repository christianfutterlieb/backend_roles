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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * ExtensionInformationProvider
 */
class ExtensionInformationProvider
{
    /**
     * @return array<int, string>
     */
    public function getLoadedExtensionListArray(): array
    {
        return ExtensionManagementUtility::getLoadedExtensionListArray();
    }

    public function extPath(string $extKey): string
    {
        return ExtensionManagementUtility::extPath($extKey);
    }
}
