<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Tests\Unit\Role;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Exception\RoleDefinitionException;
use AawTeam\BackendRoles\Role\Definition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * DefinitionTest
 */
class DefinitionTest extends UnitTestCase
{
    #[Test]
    public function objectIdentificationTest(): void
    {
        $identifier = 'test';
        $definition = new Definition($identifier);

        self::assertSame($identifier, $definition->getIdentifier());
    }

    #[Test]
    public function defaultOptionValuesAreAlwaysNull(): void
    {
        $identifier = 'test';
        $definition = new Definition($identifier);

        self::assertNull($definition->getTitle());
        self::assertNull($definition->getTSConfig());
        self::assertNull($definition->getPagetypesSelect());
        self::assertNull($definition->getTablesSelect());
        self::assertNull($definition->getTablesModify());
        self::assertNull($definition->getGroupMods());
        self::assertNull($definition->getFilePermissions());
        self::assertNull($definition->getAllowedLanguages());
        self::assertNull($definition->getExplicitAllowdeny());
        self::assertNull($definition->getNonExcludeFields());
    }

    #[Test]
    public function optionValuesAreSetCorrectlyByConstructor(): void
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
            'allowed_languages' => ['nonExcludeFields'],
            'explicit_allowdeny' => ['explicitAllowdeny'],
            'non_exclude_fields' => ['nonExcludeFields'],
        ];

        $definition = new Definition($identifier, $options);

        // Note: $definition->getIdentifier() is tested by objectIdentificationTest()
        self::assertSame($definition->getTitle(), $options['title']);
        self::assertSame($definition->getTSConfig(), $options['TSconfig']);
        self::assertSame($definition->getPagetypesSelect(), $options['pagetypes_select']);
        self::assertSame($definition->getTablesSelect(), $options['tables_select']);
        self::assertSame($definition->getTablesModify(), $options['tables_modify']);
        self::assertSame($definition->getGroupMods(), $options['groupMods']);
        self::assertSame($definition->getFilePermissions(), $options['file_permissions']);
        self::assertSame($definition->getAllowedLanguages(), $options['allowed_languages']);
        self::assertSame($definition->getExplicitAllowdeny(), $options['explicit_allowdeny']);
        self::assertSame($definition->getNonExcludeFields(), $options['non_exclude_fields']);
    }

    #[Test]
    public function toArrayDoesNotCreateOffsetsForNotSetOptions(): void
    {
        $identifier = 'test';
        $options = [
            'identifier' => $identifier,
            'title' => 'test',
            'pagetypes_select' => ['pagetypesSelect'],
        ];

        $definition = new Definition($identifier, $options);
        self::assertSame($definition->toArray(), $options);
    }

    #[Test]
    public function toArrayReturnsAllTheCorrectValues(): void
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
            'allowed_languages' => ['allowedLanguages'],
            'explicit_allowdeny' => ['explicitAllowdeny'],
            'non_exclude_fields' => ['nonExcludeFields'],
        ];

        $definition = new Definition($identifier, $options);
        self::assertSame($definition->toArray(), $options);
    }

    /**
     * @param mixed[] $options
     */
    #[Test]
    #[DataProvider('objectConstructorThrowsExceptionWithInvalidDataDataProvider')]
    public function objectConstructorThrowsExceptionWithInvalidData(string $identifier, array $options): void
    {
        $this->expectException(RoleDefinitionException::class);
        new Definition($identifier, $options);
    }

    /**
     * @return mixed[]
     */
    public static function objectConstructorThrowsExceptionWithInvalidDataDataProvider(): array
    {
        $dataSet = [
            'identifier-is-empty-string' => [
                '', [],
            ],
            'identifier-contains-only-whitespace' => [
                " \n\t", [],
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
                ],
            ];
            $dataSet[$optionName . '-is-bool'] = [
                $validIdentifier, [
                    $optionName => true,
                ],
            ];
            $dataSet[$optionName . '-is-int'] = [
                $validIdentifier, [
                    $optionName => 42,
                ],
            ];
            $dataSet[$optionName . '-is-float'] = [
                $validIdentifier, [
                    $optionName => 42.0,
                ],
            ];
            $dataSet[$optionName . '-is-array'] = [
                $validIdentifier, [
                    $optionName => [],
                ],
            ];
            $dataSet[$optionName . '-is-object'] = [
                $validIdentifier, [
                    $optionName => new \stdClass(),
                ],
            ];
        }
        $testForArrayOptions = [
            'pagetypes_select', 'tables_select', 'tables_modify', 'groupMods', 'file_permissions', 'allowed_languages', 'explicit_allowdeny', 'non_exclude_fields',
        ];
        foreach ($testForArrayOptions as $optionName) {
            $dataSet[$optionName . '-is-null'] = [
                $validIdentifier, [
                    $optionName => null,
                ],
            ];
            $dataSet[$optionName . '-is-bool'] = [
                $validIdentifier, [
                    $optionName => true,
                ],
            ];
            $dataSet[$optionName . '-is-int'] = [
                $validIdentifier, [
                    $optionName => 42,
                ],
            ];
            $dataSet[$optionName . '-is-float'] = [
                $validIdentifier, [
                    $optionName => 42.0,
                ],
            ];
            $dataSet[$optionName . '-is-string'] = [
                $validIdentifier, [
                    $optionName => '42',
                ],
            ];
            $dataSet[$optionName . '-is-object'] = [
                $validIdentifier, [
                    $optionName => new \stdClass(),
                ],
            ];
        }

        return $dataSet;
    }
}
