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

use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * RoleTitleViewHelper
 */
class RoleTitleViewHelper extends AbstractViewHelper
{
    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var Loader
     */
    protected $loader;

    /**
     * @param Formatter $formatter
     */
    public function injectFormatter(Formatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * @param Loader $loader
     */
    public function injectLoader(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper::initializeArguments()
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

        // No role
        if (!is_string($backendUserGroup['tx_backendroles_role_identifier'] ?? null) || trim($backendUserGroup['tx_backendroles_role_identifier']) === '') {
            return '';
        }

        $roleDefinitions = $this->loader->getRoleDefinitions();
        if (!array_key_exists($backendUserGroup['tx_backendroles_role_identifier'], $roleDefinitions)) {
            return '[UNKNOWN ROLE IDENTIFIER "' . $backendUserGroup['tx_backendroles_role_identifier'] . '"]';
        }

        return $this->formatter->formatTitle($roleDefinitions[$backendUserGroup['tx_backendroles_role_identifier']]);
    }
}
