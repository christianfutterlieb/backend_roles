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
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
        'allowed_languages',
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
        if (($value = $definition->getAllowedLanguages()) !== null) {
            $formatted['allowed_languages'] = implode(',', $value);
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

    /**
     * @param array $backendUserGroup
     * @return array
     * @todo Should we use the input data filtering here?
     */
    public function formatFromDbToArray(array $backendUserGroup): array
    {
//         $defaultValues = $this->getManagedColumnsWithDefaultValues();
//         $dataToProcess = array_filter($backendUserGroup, function ($v, $k) use ($defaultValues) {
//             if (array_key_exists($k, $defaultValues)) {
//                 return $defaultValues[$k] !== $v;
//             }
//             return false;
//         }, ARRAY_FILTER_USE_BOTH);
        $dataToProcess = $backendUserGroup;

        $return = [];

        // Strings
        if (array_key_exists('TSconfig', $dataToProcess)) {
            $return['TSconfig'] = $dataToProcess['TSconfig'];
        }

        // Comma-separated to array
        if (array_key_exists('pagetypes_select', $dataToProcess)) {
            $return['pagetypes_select'] = explode(',', $dataToProcess['pagetypes_select']);
            array_walk($return['pagetypes_select'], function (&$v) {
                $v = (int)$v;
            });
        }
        if (array_key_exists('tables_select', $dataToProcess)) {
            $return['tables_select'] = explode(',', $dataToProcess['tables_select']);
        }
        if (array_key_exists('tables_modify', $dataToProcess)) {
            $return['tables_modify'] = explode(',', $dataToProcess['tables_modify']);
        }
        if (array_key_exists('groupMods', $dataToProcess)) {
            $return['groupMods'] = explode(',', $dataToProcess['groupMods']);
        }
        if (array_key_exists('file_permissions', $dataToProcess)) {
            $return['file_permissions'] = explode(',', $dataToProcess['file_permissions']);
        }
        if (array_key_exists('allowed_languages', $dataToProcess)) {
            $return['allowed_languages'] = explode(',', $dataToProcess['allowed_languages']);
            array_walk($return['allowed_languages'], function (&$v) {
                $v = (int)$v;
            });
        }

        // Comma-separated to multi-array
        foreach (['explicit_allowdeny', 'non_exclude_fields'] as $option) {
            if (array_key_exists($option, $dataToProcess)) {
                $final = [];
                foreach (explode(',', $dataToProcess[$option]) as $entry) {
                    if (trim($entry) === '') {
                        continue;
                    } elseif (strpos($entry, ';') !== false) {
                        list($path, $ffPath) = explode(';', $entry, 2);
                        $parts = explode(';', $ffPath);
                        $value = array_pop($parts);
                        array_unshift($parts, $path);
                    } else {
                        $parts = explode(':', $entry, 2);
                        $value = array_pop($parts);
                    }

                    $finalPath = implode(':', $parts);

                    if (ArrayUtility::isValidPath($final, $finalPath, ':')) {
                        $previous = ArrayUtility::getValueByPath($final, $finalPath, ':');
                        $previous[] = $value;
                        $final = ArrayUtility::setValueByPath($final, $finalPath, $previous, ':');
                    } else {
                        $final = ArrayUtility::setValueByPath($final, $finalPath, [$value], ':');
                    }
                }
                $return[$option] = $final;
            }
        }

        // Sort the array to be returned
        foreach ($return as $key => $value) {
            if (is_array($value)) {
                $return[$key] = self::sortArrayForFormatRecursive($value);
            }
        }

        return $return;
    }

    /**
     * Sort algorithm:
     *
     *   1. Numerical indexed arrays: sort by value (natsort())
     *   2. String indexed arrays: sort by key (strnatcmp())
     *   3. Mixed indexed arrays:
     *      a. Split in numeric and string indexed arrays
     *      b. Perform above logic on either of the arrays
     *      c. Merge the sorted arrays back together (numeric first)
     *
     * @param array $array
     * @return array
     */
    public static function sortArrayForFormatRecursive(array $array): array
    {
        $allKeysAreNumeric = true;
        $allKeysAreString = true;
        foreach ($array as $key => $value) {
            if ($allKeysAreNumeric && !is_int($key)) {
                $allKeysAreNumeric = false;
            }
            if ($allKeysAreString && !is_string($key)) {
                $allKeysAreString = false;
            }
            if (is_array($value)) {
                $array[$key] = self::sortArrayForFormatRecursive($value);
            }
        }

        if ($allKeysAreNumeric) {
            $return = self::sortNumericIndexedArrayForFormat($array);
        } elseif ($allKeysAreString) {
            $return = self::sortStringIndexedArrayForFormat($array);
        } else {
            // Mixed indexing: split array in numeric- and string-indexed arrays
            // and then merge numeric first
            $tmp = [];
            foreach ($array as $key => $value) {
                if (!is_int($key)) {
                    $tmp[$key] = $value;
                    unset($array[$key]);
                }
            }
            $return = array_merge(
                self::sortNumericIndexedArrayForFormat($array),
                self::sortStringIndexedArrayForFormat($tmp)
            );
        }

        return $return;
    }

    /**
     * @param array $array
     * @return array
     */
    private function sortNumericIndexedArrayForFormat(array $array): array
    {
        natsort($array);
        return array_values($array);
    }

    /**
     * @param array $array
     * @return array
     */
    private function sortStringIndexedArrayForFormat(array $array): array
    {
        uksort($array, 'strnatcmp');
        return $array;
    }
}
