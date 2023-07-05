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

use AawTeam\BackendRoles\Controller\ManagementController;

return [
    'system_BackendRolesManagement' => [
        'parent' => 'system',
        'position' => ['after' => 'backend_user_management'],
        'access' => 'admin',
        'iconIdentifier' => 'backend_roles-module-management',
        'labels' => 'LLL:EXT:backend_roles/Resources/Private/Language/ModuleLabels.xlf',
        'extensionName' => 'BackendRoles',
        'controllerActions' => [
            ManagementController::class => 'index, synchronizeAllBackendUserGroupRoles, resetBackendUserGroupToDefaults, exportAsRole',
        ],
    ],
];
