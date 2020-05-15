<?php
/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3_MODE') or die();

$bootstrap = function () {

    // Register the backend module
    $extensionName = 'BackendRoles';
    $controllerActions = [
        \AawTeam\BackendRoles\Controller\ManagementController::class => 'index, synchronizeAllBackendUserGroupRoles, resetBackendUserGroupToDefaults, exportAsRole',
    ];
    // Old-style for TYPO3 versions below 10
    if (version_compare(TYPO3_version, '10', '<')) {
        $extensionName = 'AawTeam.' . $extensionName;

        $controllerAliases = [
            \AawTeam\BackendRoles\Controller\ManagementController::class => 'Management',
        ];
        foreach ($controllerAliases as $controllerClass => $controllerAlias) {
            $controllerActions[$controllerAlias] = $controllerActions[$controllerClass];
            unset($controllerActions[$controllerClass]);
        }
    }

    // Add the backend module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $extensionName,
        'tools',
        'management',
        '',
        $controllerActions,
        [
            'access' => 'admin',
            // @todo add icon
            //'iconIdentifier' => '',
            'labels' => 'LLL:EXT:backend_roles/Resources/Private/Language/ModuleLabels.xlf',
        ]
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
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

    if (version_compare(TYPO3_version, '10', '<')) {
        // See https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.0/Breaking-87623-ReplaceConfigpersistenceclassesTyposcriptConfiguration.html
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
config.tx_extbase.persistence.classes {
    ' . \AawTeam\BackendRoles\Domain\Model\BackendUserGroup::class . ' {
        mapping {
            tableName = be_groups
            columns {
                tx_backendroles_role_identifier.mapOnProperty = roleIdentifier
            }
        }
    }
}
        ');
    }
};
$bootstrap();
unset($bootstrap);
