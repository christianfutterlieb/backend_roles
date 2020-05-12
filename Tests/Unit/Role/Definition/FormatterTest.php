<?php
declare(strict_types=1);
namespace AawTeam\LanguageMatcher\Tests\Unit\Context\Context;

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
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * FormatterTest
 */
class FormatterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function managedColumnsApiTest()
    {
        $formatter = new Formatter();
        $this->assertSame($formatter->getManagedColumnNames(), array_keys($formatter->getManagedColumnsWithDefaultValues()));
    }

    /**
     * @test
     */
    public function returnDefaultsWhenInputIsEmpty()
    {
        $formatter = new Formatter();
        $definition = new Definition('test');
        $this->assertSame($formatter->getManagedColumnsWithDefaultValues(), $formatter->formatForDatabase($definition));
    }

    /**
     * @test
     * @dataProvider formatTitleTestDataProvider
     */
    public function formatTitleTest(Definition $definition, string $expectedTitle)
    {
        $formatter = new Formatter();
        $this->assertSame($expectedTitle, $formatter->formatTitle($definition));
    }

    /**
     * @return array
     */
    public function formatTitleTestDataProvider(): array
    {
        $identifier = 'test';
        return [
            'no-title-at-all-returns-identifier' => [
                new Definition($identifier),
                $identifier
            ],
            'empty-title-returns-identifier' => [
                new Definition($identifier, ['title' => '']),
                $identifier
            ],
            'whitespace-only-title-returns-identifier' => [
                new Definition($identifier, ['title' => " \n\t "]),
                $identifier
            ],
            'title-that-can-be-interpreted-as-empty-but-is-not-returns-title' => [
                new Definition($identifier, ['title' => '0']),
                '0'
            ],
            'normal-title' => [
                new Definition($identifier, ['title' => 'My title']),
                'My title'
            ],
        ];
    }

    /**
     * @test
     */
    public function formatForDatabaseStringValuesTest()
    {
        $identifier = 'test';
        $options = [
            'title' => 'My Title',
            'TSconfig' => 'My TSConfig',
        ];

        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);
        $this->assertArrayNotHasKey('title', $result);

        $this->assertArrayHasKey('TSconfig', $result);
        $this->assertSame('My TSConfig', $result['TSconfig']);
    }

    /**
     * @test
     */
    public function formatForDatabaseSimpleArrayValuesTest()
    {
        $identifier = 'test';
        $options = [
            'pagetypes_select' => ['My', 'pagetypes', 'select'],
            'tables_select' => ['My', 'tables', 'select'],
            'tables_modify' => ['My', 'tables', 'modify'],
            'groupMods' => ['My', 'group', 'Mods'],
            'file_permissions' => ['My', 'file', 'permissions'],
        ];

        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);

        $this->assertArrayHasKey('pagetypes_select', $result);
        $this->assertSame('My,pagetypes,select', $result['pagetypes_select']);

        $this->assertArrayHasKey('tables_select', $result);
        $this->assertSame('My,tables,select', $result['tables_select']);

        $this->assertArrayHasKey('tables_modify', $result);
        $this->assertSame('My,tables,modify', $result['tables_modify']);

        $this->assertArrayHasKey('groupMods', $result);
        $this->assertSame('My,group,Mods', $result['groupMods']);

        $this->assertArrayHasKey('file_permissions', $result);
        $this->assertSame('My,file,permissions', $result['file_permissions']);
    }

    /**
     * @test
     * @dataProvider formatForDatabaseComplexArrayValuesTestDataProvider
     */
    public function formatForDatabaseComplexArrayValuesTest(string $optionName, array $optionValue, string $expectedFormattedValue)
    {
        $identifier = 'test';
        $options = [
            $optionName => $optionValue,
        ];

        $definition = new Definition($identifier, $options);
        $formatter = new Formatter();
        $result = $formatter->formatForDatabase($definition);

        $this->assertArrayHasKey($optionName, $result);
        $this->assertSame($expectedFormattedValue, $result[$optionName]);
    }

    /**
     * @return array
     */
    public function formatForDatabaseComplexArrayValuesTestDataProvider(): array
    {
        return [
            'explicit_allowdeny-empty' => [
                'explicit_allowdeny',
                [],
                ''
            ],
            'non_exclude_fields-empty' => [
                'non_exclude_fields',
                [],
                ''
            ],
            'explicit_allowdeny-simple' => [
                'explicit_allowdeny',
                [
                    'tt_content' => ['CType:header:ALLOW']
                ],
                'tt_content:CType:header:ALLOW'
            ],
            'non_exclude_fields-simple' => [
                'non_exclude_fields',
                [
                    'tt_content' => ['CType']
                ],
                'tt_content:CType'
            ],
            'explicit_allowdeny-more-fields' => [
                'explicit_allowdeny',
                [
                    'tt_content' => ['CType:header:ALLOW', 'CType:textmedia:ALLOW']
                ],
                'tt_content:CType:header:ALLOW,tt_content:CType:textmedia:ALLOW'
            ],
            'non_exclude_fields-more-fields' => [
                'non_exclude_fields',
                [
                    'tt_content' => ['CType', 'header']
                ],
                'tt_content:CType,tt_content:header'
            ],
            'non_exclude_fields-with-flexforms-simple-1' => [
                'non_exclude_fields',
                [
                    'tt_content' => [
                        'pi_flexform' => [
                            'settings.pages',
                        ],
                    ]
                ],
                'tt_content:pi_flexform;settings.pages'
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
                    ]
                ],
                'tt_content:pi_flexform;sDEF;settings.pages'
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
                    ]
                ],
                'tt_content:pi_flexform;login;sDEF;settings.pages'
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
                'tx_teams_person:options;sDEF;myoption'
            ],
        ];
    }
}
