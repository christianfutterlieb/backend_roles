<?php
/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use AawTeam\BackendRoles\FormEngine\BackendRoleSelectItemsProcessor;
use AawTeam\BackendRoles\Role\Definition\Formatter;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Add columns
$columns = [
    'tx_backendroles_role_identifier' => [
        'exclude' => true,
        'label' => 'Managed role',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'default' => '',
            'items' => [
                ['', ''],
            ],
            'itemsProcFunc' => BackendRoleSelectItemsProcessor::class . '->process',
            'sortItems' => [
                'label' => 'asc',
            ],
        ],
    ],
];
ExtensionManagementUtility::addTCAcolumns('be_groups', $columns);
ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_backendroles_role_identifier', '', 'after:title');

// Load extension configuration
$extConf = GeneralUtility::makeInstance(
    ExtensionConfiguration::class
)->get('backend_roles');

// Add displayCond to all managed fields to hide them for the managed roles
if ($extConf['hideManagedBackendUserGroupColumnns'] ?? false) {
    $displayCond = 'FIELD:tx_backendroles_role_identifier:REQ:false';
    /** @var Formatter $roleDefinitionFormatter */
    $roleDefinitionFormatter = GeneralUtility::makeInstance(Formatter::class);
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
