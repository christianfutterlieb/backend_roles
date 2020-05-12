<?php
declare(strict_types=1);
namespace AawTeam\BackendRoles\Domain\Model;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup as ExtbaseBackendUserGroup;

/**
 * AbstractController
 */
class BackendUserGroup extends ExtbaseBackendUserGroup
{
    /**
     * @var string
     */
    protected $roleIdentifier = '';

    /**
     * @return string
     */
    public function getRoleIdentifier(): string
    {
        return $this->roleIdentifier;
    }

    /**
     * @param string $roleIdentifier
     */
    public function setRoleIdentifier(string $roleIdentifier): void
    {
        $this->roleIdentifier = $roleIdentifier;
    }
}
