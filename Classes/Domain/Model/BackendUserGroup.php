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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * BackendUserGroup
 */
class BackendUserGroup extends AbstractEntity
{
    protected string $title = '';
    protected string $roleIdentifier = '';

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getRoleIdentifier(): string
    {
        return $this->roleIdentifier;
    }

    public function setRoleIdentifier(string $roleIdentifier): void
    {
        $this->roleIdentifier = $roleIdentifier;
    }
}
