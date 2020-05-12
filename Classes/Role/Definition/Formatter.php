<?php
declare(strict_types=1);
namespace AawTeam\BackendRoles\Role\Definition;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\Definition;

/**
 * Formatter
 */
class Formatter
{
    /**
     * @var array
     */
    protected static $managedColumnNames = [
        'TSconfig',
        'pagetypes_select',
        'tables_select',
        'tables_modify',
        'groupMods',
        'file_permissions',
        'explicit_allowdeny',
        'non_exclude_fields',
    ];

    /**
     * @return array
     */
    public function getManagedColumnNames(): array
    {
        return self::$managedColumnNames;
    }

    /**
     * @return array
     */
    public function getManagedColumnsWithDefaultValues(): array
    {
        $return = [];
        foreach (self::$managedColumnNames as $managedColumnName) {
            $return[$managedColumnName] = $GLOBALS['TCA']['be_groups']['columns'][$managedColumnName]['config']['default'] ?? '';
        }
        return $return;
    }

    /**
     * @param Definition $definition
     * @return string
     */
    public function formatTitle(Definition $definition): string
    {
        $return = trim($definition->getTitle() ?? '');
        if ($return === '') {
            $return = $definition->getIdentifier();
        }
        return $return;
    }

    /**
     * @param Definition $definition
     * @return array
     */
    public function formatForDatabase(Definition $definition): array
    {
        $formatted = $this->getManagedColumnsWithDefaultValues();

        // String
        if (($value = $definition->getTSConfig()) !== null) {
            $formatted['TSconfig'] = $value;
        }

        // Array to comma-separated
        if (($value = $definition->getPagetypesSelect()) !== null) {
            $formatted['pagetypes_select'] = implode(',', $value);
        }
        if (($value = $definition->getTablesSelect()) !== null) {
            $formatted['tables_select'] = implode(',', $value);
        }
        if (($value = $definition->getTablesModify()) !== null) {
            $formatted['tables_modify'] = implode(',', $value);
        }
        if (($value = $definition->getGroupMods()) !== null) {
            $formatted['groupMods'] = implode(',', $value);
        }
        if (($value = $definition->getFilePermissions()) !== null) {
            $formatted['file_permissions'] = implode(',', $value);
        }

        // Multi-array to comma-separated
        if (($value = $definition->getExplicitAllowdeny()) !== null) {
            $formatted['explicit_allowdeny'] = $this->multiArray2CommaSeparated($value);
        }
        if (($value = $definition->getNonExcludeFields()) !== null) {
            $formatted['non_exclude_fields'] = $this->multiArray2CommaSeparated($value);
        }

        return $formatted;
    }

    /**
     * $tableName ":" $columnName [ ";" $ff1 [ ";" $ffn ] ]
     *
     * @param array $value
     * @return string
     */
    private function multiArray2CommaSeparated(array $value): string
    {
        $return = [];
        foreach ($value as $tableName => $columnNames) {
            if (is_array($columnNames)) {
                foreach ($columnNames as $columnName => $val) {
                    if (is_array($val)) {
                        foreach ($this->flattenFlexFormSubDefinitions($val, $tableName . ':' . $columnName) as $v) {
                            $return[] = $v;
                        }
                    } else {
                        $return[] = $tableName . ':' . $val;
                    }
                }
            } else {
                $return[] = $tableName . ':' . $columnNames;
            }
        }

        return implode(',', $return);
    }

    /**
     *
     * @param array $input
     * @param string $prefix
     * @return array
     */
    private function flattenFlexFormSubDefinitions(array $input, string $prefix = ''): array
    {
        $return = [];
        foreach ($input as $k => $v) {
            $newPrefix = ($prefix === '' ? '' : ($prefix . ';'));
            if (!is_array($v)) {
                $return[] = $newPrefix . $v;
            } else {
                $return = array_merge($return, $this->flattenFlexFormSubDefinitions($v, $newPrefix . $k));
            }
        }
        return $return;
    }
}
