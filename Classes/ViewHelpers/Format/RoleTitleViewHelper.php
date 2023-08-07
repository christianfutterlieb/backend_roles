<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\ViewHelpers\Format;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\Definition;
use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * RoleTitleViewHelper
 */
class RoleTitleViewHelper extends AbstractViewHelper
{
    protected Formatter $formatter;
    protected Loader $loader;

    public function __construct(
        Formatter $formatter,
        Loader $loader
    ) {
        $this->formatter = $formatter;
        $this->loader = $loader;
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::initializeArguments()
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('backendUserGroup', 'array', 'The be_groups record', true);
    }

    /**
     * @throws \InvalidArgumentException
     * @return string
     */
    public function render()
    {
        if (!is_array($this->arguments['backendUserGroup'])) {
            throw new \InvalidArgumentException('Argument "backendUserGroup" must be array');
        }
        $backendUserGroup = $this->arguments['backendUserGroup'];

        // No (valid) role
        if (!Definition::isValidIdentifier($backendUserGroup['tx_backendroles_role_identifier'] ?? null)) {
            return '';
        }

        $roleDefinitions = $this->loader->getRoleDefinitions();
        if (!$roleDefinitions->offsetExists($backendUserGroup['tx_backendroles_role_identifier'])) {
            return '[UNKNOWN ROLE IDENTIFIER "' . $backendUserGroup['tx_backendroles_role_identifier'] . '"]';
        }

        return $this->formatter->formatTitle(
            $roleDefinitions->offsetGet($backendUserGroup['tx_backendroles_role_identifier'])
        );
    }
}
