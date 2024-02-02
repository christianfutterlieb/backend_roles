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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * SynchronizationStatusFactory
 */
final class SynchronizationStatusFactory implements SynchronizationStatusFactoryInterface
{
    public function __construct(
        protected Loader $loader,
        protected readonly Formatter $formatter,
        protected DefinitionFactory $definitionFactory
    ) {}

    public function create(int $status): SynchronizationStatus
    {
        return new SynchronizationStatus($status);
    }

    public function createFromBackendGroupUid(int $backendGroupuid): SynchronizationStatus
    {
        $backendGroupRecord = BackendUtility::getRecord('be_groups', $backendGroupuid);
        if ($backendGroupRecord === null) {
            throw new \RuntimeException('Cannot load be_groups record with uid ' . $backendGroupuid, 1708168047);
        }
        return $this->createFromBackendGroupRecord($backendGroupRecord);
    }

    /**
     * @param mixed[] $backendGroupRecord
     */
    public function createFromBackendGroupRecord(array $backendGroupRecord): SynchronizationStatus
    {
        $identifier = $this->getIdentifierFromBackendGroupRecord($backendGroupRecord);

        // Not synchronized
        if ($identifier === '') {
            return $this->create(SynchronizationStatus::NONE);
        }
        $definitions = $this->loader->getRoleDefinitions();
        if (!$definitions->offsetExists($identifier)) {
            return $this->create(SynchronizationStatus::NOK);
        }

        // be_groups record is synchronized: load definitions
        /** @var Definition $definitionFromConfiguration */
        $definitionFromConfiguration = $definitions->offsetGet($identifier);
        $definitionFromDatabase = $this->definitionFactory->create(
            array_merge(
                [
                    'identifier' => $definitionFromConfiguration->getIdentifier(),
                    'title' => $definitionFromConfiguration->getTitle(),
                ],
                $this->formatter->formatFromDbToArray($backendGroupRecord)
            )
        );

        // Compare the definitions
        $status = SynchronizationStatus::NOK;
        if ($this->areRoleDefinitionsEqual($definitionFromConfiguration, $definitionFromDatabase)) {
            $status = SynchronizationStatus::OK;
        }

        return $this->create($status);
    }

    protected function areRoleDefinitionsEqual(Definition $definition1, Definition $definition2): bool
    {
        $a = $this->formatter->formatForDatabase($definition1);
        $b = $this->formatter->formatForDatabase($definition2);
        return array_diff_assoc($a, $b) === [];
    }

    /**
     * @param mixed[] $backendGroupRecord
     */
    protected function getIdentifierFromBackendGroupRecord(array $backendGroupRecord): string
    {
        // Input validation
        if (!array_key_exists('tx_backendroles_role_identifier', $backendGroupRecord)) {
            throw new \InvalidArgumentException('No field "tx_backendroles_role_identifier" found in $backendGroupRecord', 1708168285);
        }
        if (!is_string($backendGroupRecord['tx_backendroles_role_identifier'])) {
            throw new \InvalidArgumentException('Field "tx_backendroles_role_identifier" in $backendGroupRecord must be string', 1708168332);
        }

        return $backendGroupRecord['tx_backendroles_role_identifier'];
    }
}
