<?php

use AawTeam\BackendRoles\Controller\ManagementController;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3') || die();

$bootstrap = function (): void {
    // Register the backend module
    ExtensionUtility::registerModule(
        'BackendRoles',
        'tools',
        'management',
        '',
        [
            ManagementController::class => 'index, synchronizeAllBackendUserGroupRoles, resetBackendUserGroupToDefaults, exportAsRole, downloadRoleDefinition',
        ],
        [
            'access' => 'admin',
            'iconIdentifier' => 'backend_roles-module-management',
            'labels' => 'LLL:EXT:backend_roles/Resources/Private/Language/ModuleLabels.xlf',
        ]
    );
    ExtensionManagementUtility::addTypoScriptSetup('
module.tx_backendroles {
    view {
        templateRootPaths.0 = EXT:backend_roles/Resources/Private/Templates
        layoutRootPaths.0 = EXT:aawskin_template_h/Resources/Private/Layouts
        partialRootPaths.0 = EXT:aawskin_template_h/Resources/Private/Partials
    }
    settings {
    }
}
    ');
};
$bootstrap();
unset($bootstrap);
