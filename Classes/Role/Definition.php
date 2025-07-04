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

use AawTeam\BackendRoles\Exception\RoleDefinitionException;

/**
 * Definition
 */
class Definition
{
    protected string $identifier = '';
    protected ?string $title = null;
    protected ?string $tSConfig = null;
    /** @var int[] */
    protected ?array $pagetypesSelect = null;
    /** @var string[] */
    protected ?array $tablesSelect = null;
    /** @var string[] */
    protected ?array $tablesModify = null;
    /** @var string[] */
    protected ?array $groupMods = null;
    /** @var string[] */
    protected ?array $filePermissions = null;
    /** @var int[] */
    protected ?array $allowedLanguages = null;
    /** @var mixed[] */
    protected ?array $explicitAllowdeny = null;
    /** @var mixed[] */
    protected ?array $nonExcludeFields = null;

    /**
     * @param mixed[]|null $array
     */
    public function __construct(string $identifier, ?array $array = null)
    {
        if (!static::isValidIdentifier($identifier)) {
            throw new RoleDefinitionException('$identifier must be not empty string');
        }
        $this->identifier = $identifier;

        if (is_array($array)) {
            if (array_key_exists('title', $array)) {
                if (!is_string($array['title'])) {
                    throw new RoleDefinitionException('Role option "title" must be string');
                }
                $this->title = $array['title'];
            }
            if (array_key_exists('TSconfig', $array)) {
                if (!is_string($array['TSconfig'])) {
                    throw new RoleDefinitionException('Role option "TSconfig" must be string');
                }
                $this->tSConfig = $array['TSconfig'];
            }
            if (array_key_exists('pagetypes_select', $array)) {
                if (!is_array($array['pagetypes_select'])) {
                    throw new RoleDefinitionException('Role option "pagetypes_select" must be array');
                }
                $this->pagetypesSelect = $array['pagetypes_select'];
            }
            if (array_key_exists('tables_select', $array)) {
                if (!is_array($array['tables_select'])) {
                    throw new RoleDefinitionException('Role option "tables_select" must be array');
                }
                $this->tablesSelect = $array['tables_select'];
            }
            if (array_key_exists('tables_modify', $array)) {
                if (!is_array($array['tables_modify'])) {
                    throw new RoleDefinitionException('Role option "tables_modify" must be array');
                }
                $this->tablesModify = $array['tables_modify'];
            }
            if (array_key_exists('groupMods', $array)) {
                if (!is_array($array['groupMods'])) {
                    throw new RoleDefinitionException('Role option "groupMods" must be array');
                }
                $this->groupMods = $array['groupMods'];
            }
            if (array_key_exists('file_permissions', $array)) {
                if (!is_array($array['file_permissions'])) {
                    throw new RoleDefinitionException('Role option "file_permissions" must be array');
                }
                $this->filePermissions = $array['file_permissions'];
            }
            if (array_key_exists('allowed_languages', $array)) {
                if (!is_array($array['allowed_languages'])) {
                    throw new RoleDefinitionException('Role option "allowed_languages" must be array');
                }
                $this->allowedLanguages = $array['allowed_languages'];
            }
            if (array_key_exists('explicit_allowdeny', $array)) {
                if (!is_array($array['explicit_allowdeny'])) {
                    throw new RoleDefinitionException('Role option "explicit_allowdeny" must be array');
                }
                $this->explicitAllowdeny = $array['explicit_allowdeny'];
            }
            if (array_key_exists('non_exclude_fields', $array)) {
                if (!is_array($array['non_exclude_fields'])) {
                    throw new RoleDefinitionException('Role option "non_exclude_fields" must be array');
                }
                $this->nonExcludeFields = $array['non_exclude_fields'];
            }
        }
    }

    public static function isValidIdentifier(mixed $identifier): bool
    {
        return is_string($identifier)
            && trim($identifier) !== ''
            && strlen($identifier) > 0;
    }

    /**
     * @return array{
     *   identifier          : string,
     *   title              ?: string,
     *   TSconfig           ?: string,
     *   pagetypes_select   ?: int[],
     *   tables_select      ?: string[],
     *   tables_modify      ?: string[],
     *   groupMods          ?: string[],
     *   file_permissions   ?: string[],
     *   allowed_languages  ?: int[],
     *   explicit_allowdeny ?: mixed[],
     *   non_exclude_fields ?: mixed[],
     * }
     */
    public function toArray(): array
    {
        $array = [
            'identifier' => $this->identifier,
            'title' => $this->title,
            'TSconfig' => $this->tSConfig,
            'pagetypes_select' => $this->pagetypesSelect,
            'tables_select' => $this->tablesSelect,
            'tables_modify' => $this->tablesModify,
            'groupMods' => $this->groupMods,
            'file_permissions' => $this->filePermissions,
            'allowed_languages' => $this->allowedLanguages,
            'explicit_allowdeny' => $this->explicitAllowdeny,
            'non_exclude_fields' => $this->nonExcludeFields,
        ];

        return array_filter($array, fn($value): bool => $value !== null);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function getTSConfig(): ?string
    {
        return $this->tSConfig;
    }

    /**
     * @return ?int[]
     */
    public function getPagetypesSelect(): ?array
    {
        return $this->pagetypesSelect;
    }

    /**
     * @return ?string[]
     */
    public function getTablesSelect(): ?array
    {
        return $this->tablesSelect;
    }

    /**
     * @return ?string[]
     */
    public function getTablesModify(): ?array
    {
        return $this->tablesModify;
    }

    /**
     * @return ?string[]
     */
    public function getGroupMods(): ?array
    {
        return $this->groupMods;
    }

    /**
     * @return ?string[]
     */
    public function getFilePermissions(): ?array
    {
        return $this->filePermissions;
    }

    /**
     * @phpstan-return ?mixed[]
     */
    public function getExplicitAllowdeny(): ?array
    {
        return $this->explicitAllowdeny;
    }

    /**
     * @phpstan-return ?mixed[]
     */
    public function getNonExcludeFields(): ?array
    {
        return $this->nonExcludeFields;
    }

    /**
     * @return ?int[]
     */
    public function getAllowedLanguages(): ?array
    {
        return $this->allowedLanguages;
    }
}
