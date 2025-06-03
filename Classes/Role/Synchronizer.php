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

use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Synchronizer
 */
class Synchronizer
{
    public function __construct(
        protected readonly Loader $loader,
        protected readonly Formatter $formatter
    ) {}

    public function synchronizeAllBackendUserGroups(): int
    {
        $qb = $this->getConnectionForTable('ge_broups')->createQueryBuilder();
        $qb->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $qb->select('*')->from('be_groups')->where(
            $qb->expr()->neq('tx_backendroles_role_identifier', $qb->createNamedParameter('', Connection::PARAM_STR))
        );
        $affectedRows = 0;
        foreach ($qb->executeQuery()->fetchAllAssociative() as $backendUserGroup) {
            $affectedRows += $this->synchronizeBackendUserGroup($backendUserGroup);
        }
        return $affectedRows;
    }

    /**
     * @param mixed[] $backendUserGroup
     */
    public function synchronizeBackendUserGroup(array $backendUserGroup): int
    {
        $roleIdentifier = $backendUserGroup['tx_backendroles_role_identifier'] ?? null;
        if (!Definition::isValidIdentifier($roleIdentifier)) {
            return 0;
        }
        $roleDefinitions = $this->loader->getRoleDefinitions();
        if (!$roleDefinitions->offsetExists($roleIdentifier)) {
            return 0;
        }

        return $this->getConnectionForTable('be_groups')->update(
            'be_groups',
            $this->formatter->formatForDatabase(
                // @phpstan-ignore-next-line, the existence of offset ($roleIdentifier) is tested above
                $roleDefinitions->offsetGet($roleIdentifier)
            ),
            ['uid' => $backendUserGroup['uid']]
        );
    }

    public function resetManagedFieldsToDefaults(int $backendUserGroupUid): int
    {
        $updates = $this->formatter->getManagedColumnsWithDefaultValues();
        return $this->getConnectionForTable('be_groups')->update(
            'be_groups',
            $updates,
            ['uid' => $backendUserGroupUid]
        );
    }

    protected function getConnectionForTable(string $tableName): Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
    }
}
