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
use AawTeam\BackendRoles\Exception\RoleDefinitionException;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * DefinitionTest
 */
class DefinitionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function objectIdentificationTest()
    {
        $identifier = 'test';
        $definition = new Definition($identifier);

        $this->assertSame($identifier, $definition->getIdentifier());
    }

    /**
     * @test
     */
    public function defaultOptionValuesAreAlwaysNull()
    {
        $identifier = 'test';
        $definition = new Definition($identifier);

        $this->assertNull($definition->getTitle());
        $this->assertNull($definition->getTSConfig());
        $this->assertNull($definition->getPagetypesSelect());
        $this->assertNull($definition->getTablesSelect());
        $this->assertNull($definition->getTablesModify());
        $this->assertNull($definition->getGroupMods());
        $this->assertNull($definition->getFilePermissions());
        $this->assertNull($definition->getExplicitAllowdeny());
        $this->assertNull($definition->getNonExcludeFields());
    }

    /**
     * @test
     */
    public function optionValuesAreSetCorrectlyByConstructor()
    {
        $identifier = 'test';
        $options = [
            'identifier' => $identifier,
            'title' => 'test',
            'TSconfig' => 'test',
            'pagetypes_select' => ['pagetypesSelect'],
            'tables_select' => ['tablesSelect'],
            'tables_modify' => ['tablesModify'],
            'groupMods' => ['groupMods'],
            'file_permissions' => ['filePermissions'],
            'explicit_allowdeny' => ['explicitAllowdeny'],
            'non_exclude_fields' => ['nonExcludeFields'],
        ];

        $definition = new Definition($identifier, $options);

        // Note: $definition->getIdentifier() is tested by objectIdentificationTest()
        $this->assertSame($definition->getTitle(), $options['title']);
        $this->assertSame($definition->getTSConfig(), $options['TSconfig']);
        $this->assertSame($definition->getPagetypesSelect(), $options['pagetypes_select']);
        $this->assertSame($definition->getTablesSelect(), $options['tables_select']);
        $this->assertSame($definition->getTablesModify(), $options['tables_modify']);
        $this->assertSame($definition->getGroupMods(), $options['groupMods']);
        $this->assertSame($definition->getFilePermissions(), $options['file_permissions']);
        $this->assertSame($definition->getExplicitAllowdeny(), $options['explicit_allowdeny']);
        $this->assertSame($definition->getNonExcludeFields(), $options['non_exclude_fields']);
    }

    /**
     * @test
     */
    public function toArrayDoesNotCreateOffsetsForNotSetOptions()
    {
        $identifier = 'test';
        $options = [
            'identifier' => $identifier,
            'title' => 'test',
            'pagetypes_select' => ['pagetypesSelect'],
        ];

        $definition = new Definition($identifier, $options);
        $this->assertSame($definition->toArray(), $options);
    }

    /**
     * @test
     */
    public function toArrayReturnsAllTheCorrectValues()
    {
        $identifier = 'test';
        $options = [
            'identifier' => $identifier,
            'title' => 'test',
            'TSconfig' => 'test',
            'pagetypes_select' => ['pagetypesSelect'],
            'tables_select' => ['tablesSelect'],
            'tables_modify' => ['tablesModify'],
            'groupMods' => ['groupMods'],
            'file_permissions' => ['filePermissions'],
            'explicit_allowdeny' => ['explicitAllowdeny'],
            'non_exclude_fields' => ['nonExcludeFields'],
        ];

        $definition = new Definition($identifier, $options);
        $this->assertSame($definition->toArray(), $options);
    }

    /**
     * @test
     * @dataProvider objectConstructorThrowsExceptionWithInvalidDataDataProvider
     * @param string $identifier
     * @param array $options
     */
    public function objectConstructorThrowsExceptionWithInvalidData(string $identifier, array $options)
    {
        $this->expectException(RoleDefinitionException::class);
        new Definition($identifier, $options);
    }

    /**
     * @return array
     */
    public function objectConstructorThrowsExceptionWithInvalidDataDataProvider(): array
    {
        $dataSet = [
            'identifier-is-empty-string' => [
                '', []
            ],
            'identifier-contains-only-whitespace' => [
                " \n\t", []
            ],
        ];

        // Generate the type-tests for the options
        $validIdentifier = 'test';
        $testForStringOptions = [
            'title', 'TSconfig',
        ];
        foreach ($testForStringOptions as $optionName) {
            $dataSet[$optionName . '-is-null'] = [
                $validIdentifier, [
                    $optionName => null,
                ]
            ];
            $dataSet[$optionName . '-is-bool'] = [
                $validIdentifier, [
                    $optionName => true,
                ]
            ];
            $dataSet[$optionName . '-is-int'] = [
                $validIdentifier, [
                    $optionName => 42,
                ]
            ];
            $dataSet[$optionName . '-is-float'] = [
                $validIdentifier, [
                    $optionName => 42.0,
                ]
            ];
            $dataSet[$optionName . '-is-array'] = [
                $validIdentifier, [
                    $optionName => [],
                ]
            ];
            $dataSet[$optionName . '-is-object'] = [
                $validIdentifier, [
                    $optionName => new \stdClass(),
                ]
            ];
        }
        $testForArrayOptions = [
            'pagetypes_select', 'tables_select', 'tables_modify', 'groupMods', 'file_permissions', 'explicit_allowdeny', 'non_exclude_fields'
        ];
        foreach ($testForArrayOptions as $optionName) {
            $dataSet[$optionName . '-is-null'] = [
                $validIdentifier, [
                    $optionName => null,
                ]
            ];
            $dataSet[$optionName . '-is-bool'] = [
                $validIdentifier, [
                    $optionName => true,
                ]
            ];
            $dataSet[$optionName . '-is-int'] = [
                $validIdentifier, [
                    $optionName => 42,
                ]
            ];
            $dataSet[$optionName . '-is-float'] = [
                $validIdentifier, [
                    $optionName => 42.0,
                ]
            ];
            $dataSet[$optionName . '-is-string'] = [
                $validIdentifier, [
                    $optionName => '42',
                ]
            ];
            $dataSet[$optionName . '-is-object'] = [
                $validIdentifier, [
                    $optionName => new \stdClass(),
                ]
            ];
        }

        return $dataSet;
    }
}
