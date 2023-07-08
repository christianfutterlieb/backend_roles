<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\FormEngine;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;

/**
 * BackendRoleSelectItemsProcessor
 */
class BackendRoleSelectItemsProcessor
{
    public function __construct(
        private readonly Loader $loader,
        private readonly Formatter $formatter
    ) {
    }

    public function process(array &$params): void
    {
        // Only add the items to be_groups.tx_backendroles_role_identifier
        if ($params['table'] !== 'be_groups' || $params['field'] !== 'tx_backendroles_role_identifier') {
            return;
        }

        $selectItems = [];
        foreach ($this->loader->getRoleDefinitions() as $roleDefinition) {
            $selectItems[] = [
                $this->formatter->formatTitle($roleDefinition),
                $roleDefinition->getIdentifier(),
            ];
        }

        // Add items (if there are any)
        if (!empty($selectItems)) {
            // Create the empty item
            if (!is_array($params['items'] ?? null)) {
                $params['items'] = [
                    ['', ''],
                ];
            }
            $params['items'] = array_merge($params['items'], $selectItems);
        }
    }
}
