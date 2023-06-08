<?php
/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

// Prepare role identifier select items
$selectItems = [
    ['', '']
];

/** @var \TYPO3\CMS\Core\Cache\CacheManager $cacheManager */
$cacheManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
if ($cacheManager->hasCache('backend_roles')) {
    $roleDefinitionLoader = new \AawTeam\BackendRoles\Role\Definition\Loader($cacheManager->getCache('backend_roles'));
    /** @var \AawTeam\BackendRoles\Role\Definition\Formatter $formatter */
    $formatter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AawTeam\BackendRoles\Role\Definition\Formatter::class);

    foreach ($roleDefinitionLoader->getRoleDefinitions() as $roleIdentifier => $roleDefinition) {
        $selectItems[] = [
            $formatter->formatTitle($roleDefinition),
            $roleIdentifier
        ];
    }
}

// Add columns
$columns = [
    'tx_backendroles_role_identifier' => [
        'exclude' => true,
        'label' => 'Managed role',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => '',
            'items' => $selectItems,
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $columns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_backendroles_role_identifier', '', 'after:title');


// Load extension configuration
$extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
)->get('backend_roles');

// Add displayCond to all managed fields to hide them for the managed roles
if ($extConf['hideManagedBackendUserGroupColumnns'] ?? false) {
    $displayCond = 'FIELD:tx_backendroles_role_identifier:REQ:false';
    /** @var \AawTeam\BackendRoles\Role\Definition\Formatter $roleDefinitionFormatter */
    $roleDefinitionFormatter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\AawTeam\BackendRoles\Role\Definition\Formatter::class);
    foreach ($roleDefinitionFormatter->getManagedColumnNames() as $columnName) {
        if (isset($GLOBALS['TCA']['be_groups']['columns'][$columnName]['displayCond'])) {
            $GLOBALS['TCA']['be_groups']['columns'][$columnName]['displayCond'] = [
                'AND' => [
                    $GLOBALS['TCA']['be_groups']['columns'][$columnName]['displayCond'],
                    $displayCond,
                ],
            ];
        } else {
            $GLOBALS['TCA']['be_groups']['columns'][$columnName]['displayCond'] = $displayCond;
        }
    }
}
