<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Tests\Unit\Role\Definition;

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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * FormatterTest
 */
class FormatterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function managedColumnsApiTest(): void
    {
        $formatter = new Formatter();
        self::assertSame($formatter->getManagedColumnNames(), array_keys($formatter->getManagedColumnsWithDefaultValues()));
    }

    /**
     * @test
     */
    public function returnDefaultsWhenInputIsEmpty(): void
    {
        $formatter = new Formatter();
        $definition = new Definition('test');
        self::assertSame($formatter->getManagedColumnsWithDefaultValues(), $formatter->formatForDatabase($definition));
    }

    /**
     * @test
     * @dataProvider formatTitleTestDataProvider
     */
    public function formatTitleTest(Definition $definition, string $expectedTitle): void
    {
        $formatter = new Formatter();
        self::assertSame($expectedTitle, $formatter->formatTitle($definition));
    }

    /**
     * @return mixed[]
     */
    public function formatTitleTestDataProvider(): array
    {
        $identifier = 'test';
        return [
            'no-title-at-all-returns-identifier' => [
                new Definition($identifier),
                $identifier,
            ],
            'empty-title-returns-identifier' => [
                new Definition($identifier, ['title' => '']),
                $identifier,
            ],
            'whitespace-only-title-returns-identifier' => [
                new Definition($identifier, ['title' => " \n\t "]),
                $identifier,
            ],
            'title-that-can-be-interpreted-as-empty-but-is-not-returns-title' => [
                new Definition($identifier, ['title' => '0']),
                '0',
            ],
            'normal-title' => [
                new Definition($identifier, ['title' => 'My title']),
                'My title',
            ],
        ];
    }

    /**
     * @test
     */
    public function formatForDatabaseStringValuesTest(): void
    {
        $identifier = 'test';
        $options = [
            'title' => 'My Title',
            'TSconfig' => 'My TSConfig',
        ];

        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);
        self::assertArrayNotHasKey('title', $result);

        self::assertArrayHasKey('TSconfig', $result);
        self::assertSame('My TSConfig', $result['TSconfig']);
    }

    /**
     * @test
     * @dataProvider formatForDatabaseSimpleArrayValuesTestDataProvider
     * @param string $optionName
     * @param mixed[] $optionValue
     * @param string $expectedFormattedValue
     */
    public function formatForDatabaseSimpleArrayValuesTest(string $optionName, array $optionValue, string $expectedFormattedValue): void
    {
        $identifier = 'test';
        $options = [
            $optionName => $optionValue,
        ];
        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);

        self::assertArrayHasKey($optionName, $result);
        self::assertSame($expectedFormattedValue, $result[$optionName]);
    }

    /**
     * @test
     * @dataProvider formatForDatabaseComplexArrayValuesTestDataProvider
     * @param string $option
     * @param mixed[] $asArray
     * @param string $asString
     */
    public function formatFromDbToArrayComplexArrayValuesTest(string $option, array $asArray, string $asString): void
    {
        $input = [
            $option => $asString,
        ];
        $formatter = new Formatter();
        $result = $formatter->formatFromDbToArray($input);
        self::assertSame($asArray, $result[$option]);
    }

    /**
     * Caution: this dataProvider is used by two tests!
     *
     * @return mixed[]
     */
    public function formatForDatabaseSimpleArrayValuesTestDataProvider(): array
    {
        return [
            'pagetypes_select' => [
                'pagetypes_select',
                [1, 2, 3],
                '1,2,3',
            ],
            'pagetypes_select (empty)' => [
                'pagetypes_select',
                [],
                '',
            ],
            'tables_select' => [
                'tables_select',
                ['My', 'select', 'tables'],
                'My,select,tables',
            ],
            'tables_select (empty)' => [
                'tables_select',
                [],
                '',
            ],
            'tables_modify' => [
                'tables_modify',
                ['My', 'modify', 'tables'],
                'My,modify,tables',
            ],
            'tables_modify (empty)' => [
                'tables_modify',
                [],
                '',
            ],
            'groupMods' => [
                'groupMods',
                ['Mods', 'My', 'group'],
                'Mods,My,group',
            ],
            'groupMods (empty)' => [
                'groupMods',
                [],
                '',
            ],
            'file_permissions' => [
                'file_permissions',
                ['My', 'file', 'permissions'],
                'My,file,permissions',
            ],
            'file_permissions (empty)' => [
                'file_permissions',
                [],
                '',
            ],
            'allowed_languages' => [
                'allowed_languages',
                [0, 1, 2],
                '0,1,2',
            ],
            'allowed_languages (empty)' => [
                'allowed_languages',
                [],
                '',
            ],
            'allowed_languages (default language)' => [
                'allowed_languages',
                [0],
                '0',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider formatForDatabaseComplexArrayValuesTestDataProvider
     * @param string $optionName
     * @param mixed[] $optionValue
     * @param string $expectedFormattedValue
     */
    public function formatForDatabaseComplexArrayValuesTest(string $optionName, array $optionValue, string $expectedFormattedValue): void
    {
        $identifier = 'test';
        $options = [
            $optionName => $optionValue,
        ];

        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);

        self::assertArrayHasKey($optionName, $result);
        self::assertSame($expectedFormattedValue, $result[$optionName]);
    }

    /**
     * @test
     * @dataProvider formatForDatabaseSimpleArrayValuesTestDataProvider
     * @param string $optionName
     * @param mixed[] $asArray
     * @param string $asString
     */
    public function formatFromDbToArraySimpleArrayValuesTest(string $optionName, array $asArray, string $asString): void
    {
        $input = [
            $optionName => $asString,
        ];
        $formatter = new Formatter();
        $result = $formatter->formatFromDbToArray($input);
        self::assertArrayHasKey($optionName, $result);
        self::assertSame($asArray, $result[$optionName]);
    }

    /**
     * Caution: this dataProvider is used by two tests!
     *
     * @return mixed[]
     */
    public function formatForDatabaseComplexArrayValuesTestDataProvider(): array
    {
        return [
            'explicit_allowdeny-empty' => [
                'explicit_allowdeny',
                [],
                '',
            ],
            'non_exclude_fields-empty' => [
                'non_exclude_fields',
                [],
                '',
            ],
            'explicit_allowdeny-simple' => [
                'explicit_allowdeny',
                [
                    'tt_content' => ['CType:header:ALLOW'],
                ],
                'tt_content:CType:header:ALLOW',
            ],
            'non_exclude_fields-simple' => [
                'non_exclude_fields',
                [
                    'tt_content' => ['CType'],
                ],
                'tt_content:CType',
            ],
            'explicit_allowdeny-more-fields' => [
                'explicit_allowdeny',
                [
                    'tt_content' => ['CType:header:ALLOW', 'CType:textmedia:ALLOW'],
                ],
                'tt_content:CType:header:ALLOW,tt_content:CType:textmedia:ALLOW',
            ],
            'non_exclude_fields-more-fields' => [
                'non_exclude_fields',
                [
                    'tt_content' => ['CType', 'header'],
                ],
                'tt_content:CType,tt_content:header',
            ],
            'non_exclude_fields-with-flexforms-simple-1' => [
                'non_exclude_fields',
                [
                    'tt_content' => [
                        'pi_flexform' => [
                            'settings.pages',
                        ],
                    ],
                ],
                'tt_content:pi_flexform;settings.pages',
            ],
            'non_exclude_fields-with-flexforms-simple-2' => [
                'non_exclude_fields',
                [
                    'tt_content' => [
                        'pi_flexform' => [
                            'sDEF' => [
                                'settings.pages',
                            ],
                        ],
                    ],
                ],
                'tt_content:pi_flexform;sDEF;settings.pages',
            ],
            'non_exclude_fields-with-flexforms-like-normal' => [
                'non_exclude_fields',
                [
                    'tt_content' => [
                        'pi_flexform' => [
                            'login' => [
                                'sDEF' => [
                                    'settings.pages',
                                ],
                            ],
                        ],
                    ],
                ],
                'tt_content:pi_flexform;login;sDEF;settings.pages',
            ],
            'non_exclude_fields-with-multiple-flexforms' => [
                'non_exclude_fields',
                [
                    'tt_content' => [
                        'pi_flexform' => [
                            'login' => [
                                'sDEF' => [
                                    'settings.pages',
                                ],
                            ],
                            'teams_person' => [
                                'appearance' => [
                                    'settings.centered',
                                    'settings.roundImage',
                                ],
                            ],
                        ],
                    ],
                    'tx_teams_person' => [
                        'options' => [
                            'sDEF' => [
                                'myoption',
                            ],
                        ],
                    ],
                ],
                'tt_content:pi_flexform;login;sDEF;settings.pages,' .
                'tt_content:pi_flexform;teams_person;appearance;settings.centered,' .
                'tt_content:pi_flexform;teams_person;appearance;settings.roundImage,' .
                'tx_teams_person:options;sDEF;myoption',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sortArrayForFormatRecursiveTestDataProvider
     * @param mixed[] $input
     * @param mixed[] $expectedOutput
     */
    public function sortArrayForFormatRecursiveTest(array $input, array $expectedOutput): void
    {
        self::assertSame($expectedOutput, Formatter::sortArrayForFormatRecursive($input));
    }

    /**
     * @return mixed[]
     */
    public function sortArrayForFormatRecursiveTestDataProvider(): array
    {
        return [
            'numeric indexed strings' => [
                ['c', 'b', 'a'],
                [0 => 'a', 1 => 'b', 2 => 'c'],
            ],
            'numeric indexed numbers' => [
                [3, 2, 1],
                [0 => 1, 1 => 2, 2 => 3],
            ],
            'numeric indexed strings and numbers' => [
                ['3', 'c', '2', 'b', 'a', '1'],
                [0 => '1', 1 => '2', 2 => '3', 3 => 'a', 4 => 'b', 5 => 'c'],
            ],
            'string indexed strings' => [
                ['c' => 'y', 'a' => 'x', 'b' => 'z'],
                ['a' => 'x', 'b' => 'z', 'c' => 'y'],
            ],
            'string indexed numbers' => [
                ['c' => 2, 'a' => 1, 'b' => 3],
                ['a' => 1, 'b' => 3, 'c' => 2],
            ],
            'string indexed strings and numbers' => [
                ['c' => 2, 'x' => 'q', 'a' => 1, 'b' => 3, 'z' => 'w', 'y' => 'e'],
                ['a' => 1, 'b' => 3, 'c' => 2, 'x' => 'q', 'y' => 'e', 'z' => 'w'],
            ],
            'mixed indexed strings' => [
                [0 => 'b', 'a' => 'z', 1 => 'a', 'b' => 'y'],
                [0 => 'a', 1 => 'b', 'a' => 'z', 'b' => 'y'],
            ],
            'mixed indexed numbers' => [
                [0 => 4, 'a' => 3, 1 => 2, 'b' => 1],
                [0 => 2, 1 => 4, 'a' => 3, 'b' => 1],
            ],
            'mixed indexed strings and numbers' => [
                [0 => 4, 'a' => 3, 'c' => 0, 1 => 'q', 10 => 1, 'b' => 'g', 9 => 'a'],
                [0 => 1, 1 => 4, 2 => 'a', 3 => 'q', 'a' => 3, 'b' => 'g', 'c' => 0],
            ],
        ];
    }

    /**
     * @test
     */
    public function formatFromDbToArrayIgnoresFieldsWithNullValue(): void
    {
        $formatter = new Formatter();

        // Set every option to be null
        $input = array_combine(
            $formatter->getManagedColumnNames(),
            array_fill(0, count($formatter->getManagedColumnNames()), null)
        );
        // 'allowed_languages' cannot be null
        unset($input['allowed_languages']);

        $result = $formatter->formatFromDbToArray($input);
        foreach (array_keys($input) as $option) {
            self::assertArrayNotHasKey($option, $result, var_export($result, true));
        }
    }
}
