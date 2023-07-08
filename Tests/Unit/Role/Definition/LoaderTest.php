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
use AawTeam\BackendRoles\Role\Definition;
use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Definition\Loader;
use AawTeam\BackendRoles\Role\DefinitionFactory;
use AawTeam\BackendRoles\Role\ExtensionInformationProvider;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

    protected function getLoaderInstance(ExtensionInformationProvider $extensionInformationProviderMock): Loader
    {
        return new Loader(
            $extensionInformationProviderMock,
            new DefinitionFactory(),
            $this->getCacheMock()
        );
    }

    /**
     * @test
     * @dataProvider invalidConfigurationFileThrowsExceptionDataProvider
     * @param string $configurationFileContents
     * @param int $expectedExceptionCode
     */
    public function invalidConfigurationFileThrowsException(string $configurationFileContents)
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

        $loader = $this->getLoaderInstance($extensionInformationProviderMock);

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode(1589311592);
        $loader->getRoleDefinitions();
    }

    /**
     * @return array
     */
    public static function invalidConfigurationFileThrowsExceptionDataProvider(): array
    {
        return [
            // Invalid file contents (no array)
            'configuration-file-returns-null' => [
                '<?php return null;',
            ],
            'configuration-file-returns-bool' => [
                '<?php return true;',
            ],
            'configuration-file-returns-int' => [
                '<?php return 1;',
            ],
            'configuration-file-returns-float' => [
                '<?php return 1.0;',
            ],
            'configuration-file-returns-string' => [
                '<?php return "1";',
            ],
            'configuration-file-returns-object' => [
                '<?php return new \\stdClass();',
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

        $loader = $this->getLoaderInstance($extensionInformationProviderMock);
        self::assertSame([], $loader->getRoleDefinitions()->toArray());
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

        $loader = $this->getLoaderInstance($extensionInformationProviderMock);
        self::assertArrayHasKey($identifier, $loader->getRoleDefinitions());
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

        $loader = $this->getLoaderInstance($extensionInformationProviderMock);
        $actualResult = $loader->getRoleDefinitions();
        self::assertCount(1, $actualResult);
        self::assertArrayHasKey($identifier, $actualResult);
        self::assertInstanceOf(Definition::class, $actualResult[$identifier]);
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

        $loader = $this->getLoaderInstance($extensionInformationProviderMock);
        $roleDefinitions = $loader->getRoleDefinitions();
        self::assertArrayNotHasKey($unknownOffset, $roleDefinitions[$identifier]);
        self::assertEmpty(array_diff(array_keys($roleDefinitions[$identifier]), $knownOffsets));
    }
}
