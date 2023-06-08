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

use AawTeam\BackendRoles\Exception\RoleDefinitionException;
use AawTeam\BackendRoles\Role\ExtensionInformationProvider;
use AawTeam\BackendRoles\Role\Definition;
use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;

/**
 * LoaderTest
 */
class LoaderTest extends UnitTestCase
{
    /**
     * @return PhpFrontend
     */
    protected function getCacheMock(): PhpFrontend
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->willReturn(false);
        return $cacheMock;
    }

    /**
     * @test
     * @dataProvider invalidConfigurationFileThrowsExceptionDataProvider
     * @param string $configurationFileContents
     * @param int $expectedExceptionCode
     */
    public function invalidConfigurationFileThrowsException(string $configurationFileContents, int $expectedExceptionCode)
    {
        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.php' => $configurationFileContents,
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        $loader->getRoleDefinitions();
    }

    /**
     * @return array
     */
    public function invalidConfigurationFileThrowsExceptionDataProvider(): array
    {
        return [
            // Invalid file contents (no array)
            'configuration-file-returns-null' => [
                '<?php return null;',
                1589311592,
            ],
            'configuration-file-returns-bool' => [
                '<?php return true;',
                1589311592,
            ],
            'configuration-file-returns-int' => [
                '<?php return 1;',
                1589311592,
            ],
            'configuration-file-returns-float' => [
                '<?php return 1.0;',
                1589311592,
            ],
            'configuration-file-returns-string' => [
                '<?php return "1";',
                1589311592,
            ],
            'configuration-file-returns-object' => [
                '<?php return new \\stdClass();',
                1589311592,
            ],
            // Invalid configurations
            'role-definition-is-null' => [
                '<?php return [ null ];',
                1589386642
            ],
            'role-definition-is-bool' => [
                '<?php return [ true ];',
                1589386642
            ],
            'role-definition-is-int' => [
                '<?php return [ 1 ];',
                1589386642
            ],
            'role-definition-is-float' => [
                '<?php return [ 1.0 ];',
                1589386642
            ],
            'role-definition-is-string' => [
                '<?php return [ "1" ];',
                1589386642
            ],
            'role-definition-is-object' => [
                '<?php return [ new \\stdClass() ];',
                1589386642
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidRoleDefinitionThrowsExceptionDataProvider
     * @param array $configurationDir
     * @param int $expectedExceptionCode
     */
    public function invalidRoleDefinitionThrowsException(array $roleDefinitions, int $expectedExceptionCode)
    {
        $roleDefinitionsCode = var_export($roleDefinitions, true);

        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.php' => '<?php return ' . $roleDefinitionsCode . ';',
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode($expectedExceptionCode);
        $loader->getRoleDefinitions();
    }

    /**
     * @return array
     */
    public function invalidRoleDefinitionThrowsExceptionDataProvider(): array
    {
        return [
            'array-without-identifier' => [
                [
                    [],
                ],
                1589387779,
            ],
            'identifier-is-null' => [
                [
                    ['identifier' => null],
                ],
                1589387779,
            ],
            'identifier-is-bool' => [
                [
                    ['identifier' => true],
                ],
                1589387779,
            ],
            'identifier-is-int' => [
                [
                    ['identifier' => 1],
                ],
                1589387779,
            ],
            'identifier-is-float' => [
                [
                    ['identifier' => 1.0],
                ],
                1589387779,
            ],
            'identifier-is-object' => [
                [
                    ['identifier' => new \stdClass()],
                ],
                1589387779,
            ],
            'identifier-is-empty-string' => [
                [
                    ['identifier' => ''],
                ],
                1589387779,
            ],
            'identifier-contains-only-whitespaces' => [
                [
                    ['identifier' => " \n\t "],
                ],
                1589387779,
            ],
            'identifier-duplicates' => [
                [
                    ['identifier' => 'id'],
                    ['identifier' => 'id'],
                ],
                1589387862,
            ],
        ];
    }

    /**
     * @test
     */
    public function whenNoConfigFileIsFoundNoRoleDefinitionsAreProduced()
    {
        vfsStream::setup('root', null, [
            'typo3conf' => [
                'ext' => [
                    'myext1' => [
                        // No configuration dir
                    ],
                    'myext2' => [
                        // Empty configuration dir
                        'Configuration' => [],
                    ],
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext1', 'myext2']);
        $extensionInformationProviderMock->method('extPath')->willReturnCallback(function ($extKey) {
            return 'vfs://root/typo3conf/ext/' . $extKey;
        });

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);
        $this->assertSame([], $loader->getRoleDefinitions());
    }

    /**
     * @test
     */
    public function roleDefinitionIdentifierIsUsedAsArrayOffset()
    {
        $identifier = 'id';
        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.php' => '<?php return [ ["identifier" => "' . $identifier . '"] ];',
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);
        $this->assertArrayHasKey($identifier, $loader->getRoleDefinitions());
    }

    /**
     * @test
     */
    public function correctlyProcessARoleDefinition()
    {
        $identifier = 'id';
        $roleDefinitionArray = [
            'identifier' => $identifier,
        ];

        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.php' => '<?php return [ ' . var_export($roleDefinitionArray, true) . ' ];',
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);
        $actualResult = $loader->getRoleDefinitions();
        $this->assertCount(1, $actualResult);
        $this->assertArrayHasKey($identifier, $actualResult);
        $this->assertInstanceOf(Definition::class, $actualResult[$identifier]);
    }

    /**
     * @todo Should we 'sanitize' the role definition array?
     */
    private function roleDefinitionsDoNotContainUnknownOffsets()
    {
        $identifier = 'id';
        $unknownOffset = 'unknown';

        $knownOffsets = (new Formatter())->getManagedColumnNames();
        $knownOffsets[] = 'identifier';
        $knownOffsets[] = 'title';

        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ["identifier" => "' . $identifier . '", "' . $unknownOffset . '" => "some value", "silly" => "value"];',
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $loader = new Loader($this->getCacheMock(), $extensionInformationProviderMock);
        $roleDefinitions = $loader->getRoleDefinitions();
        $this->assertArrayNotHasKey($unknownOffset, $roleDefinitions[$identifier]);
        $this->assertEmpty(array_diff(array_keys($roleDefinitions[$identifier]), $knownOffsets));
    }
}
