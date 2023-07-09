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
use AawTeam\BackendRoles\Role\Definition\Loader;
use AawTeam\BackendRoles\Role\DefinitionFactory;
use AawTeam\BackendRoles\Role\ExtensionInformationProvider;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * LoaderTest
 */
class LoaderTest extends UnitTestCase
{
    protected bool $backupEnvironment = true;

    protected function getCacheMock(): PhpFrontend
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->willReturn(false);
        return $cacheMock;
    }

    protected function getLoaderInstance(ExtensionInformationProvider $extensionInformationProviderMock, ?YamlFileLoader $yamlFileLoader = null): Loader
    {
        return new Loader(
            $extensionInformationProviderMock,
            new DefinitionFactory(),
            $this->getCacheMock(),
            $yamlFileLoader ?? new YamlFileLoader()
        );
    }

    #[Test]
    #[DataProvider('invalidConfigurationFileThrowsExceptionDataProvider')]
    public function invalidConfigurationFileThrowsException(string $configurationFileContents): void
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
     * Note: yaml connfguration is always returned as array, this test only
     * applies to php-based configuration
     *
     * @see invalidYamlConfigurationStructureThrowsException()
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

    #[Test]
    #[DataProvider('invalidYamlConfigurationStructureThrowsExceptionDataProvider')]
    public function invalidYamlConfigurationStructureThrowsException(array $yamlConfiguration): void
    {
        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.yaml' => '',
                ],
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $yamlFileLoaderMock = $this->createMock(YamlFileLoader::class);
        $yamlFileLoaderMock->method('load')->willReturn($yamlConfiguration);

        $loader = $this->getLoaderInstance($extensionInformationProviderMock, $yamlFileLoaderMock);

        $this->expectException(RoleDefinitionException::class);
        $this->expectExceptionCode(1589311592);
        $loader->getRoleDefinitions();
    }

    public static function invalidYamlConfigurationStructureThrowsExceptionDataProvider(): array
    {
        return [
            'empty-array' => [
                [],
            ],
            'missing-roledefinition-key' => [
                [
                    'some' => [
                        'configuration' => 'goes-here',
                    ],
                ],
            ],
            'roledefinition-is-null' => [
                [
                    'RoleDefinitions' => null,
                ],
            ],
            'roledefinition-is-bool' => [
                [
                    'RoleDefinitions' => true,
                ],
            ],
            'roledefinition-is-int' => [
                [
                    'RoleDefinitions' => 1,
                ],
            ],
            'roledefinition-is-float' => [
                [
                    'RoleDefinitions' => 1.0,
                ],
            ],
            'roledefinition-is-string' => [
                [
                    'RoleDefinitions' => 'string',
                ],
            ],
            'roledefinition-is-object' => [
                [
                    'RoleDefinitions' => new \stdClass(),
                ],
            ],
        ];
    }

    #[Test]
    public function whenNoConfigFileIsFoundNoRoleDefinitionsAreProduced(): void
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

    #[Test]
    public function correctlyProcessARoleDefinition(): void
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

    #[Test]
    public function loadConfigsFromDifferentLocations(): void
    {
        // Fake the config path in Environment
        Environment::initialize(
            Environment::getContext(),
            Environment::isCli(),
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            'vfs://root/config',
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX',
        );

        $roleIdentifier1 = 'identifier-1';
        $roleIdentifier2 = 'identifier-2';
        $roleIdentifier3 = 'identifier-3';
        $roleIdentifier4 = 'identifier-4';

        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'RoleDefinitions.yaml' => '',
                    'RoleDefinitions.php' => '<?php return [["identifier" => "' . $roleIdentifier1 . '"]];',
                ],
            ],
            'config' => [
                'BackendRoleDefinitions.yaml' => '',
                'BackendRoleDefinitions.php' => '<?php return [["identifier" => "' . $roleIdentifier2 . '"]];',
            ],
        ]);

        $extensionInformationProviderMock = $this->createMock(ExtensionInformationProvider::class);
        $extensionInformationProviderMock->method('getLoadedExtensionListArray')->willReturn(['myext']);
        $extensionInformationProviderMock->method('extPath')->willReturn('vfs://root/myext');

        $yamlFileLoaderMock = $this->createMock(YamlFileLoader::class);
        $yamlFileLoaderMock->method('load')->willReturnCallback(function (string $fileName) use ($roleIdentifier3, $roleIdentifier4): array {
            if ($fileName === 'vfs://root/myext/Configuration/RoleDefinitions.yaml') {
                return [
                    'RoleDefinitions' => [
                        [
                            'identifier' => $roleIdentifier3,
                        ],
                    ],
                ];
            }
            if ($fileName === Environment::getConfigPath() . '/BackendRoleDefinitions.yaml') {
                return [
                    'RoleDefinitions' => [
                        [
                            'identifier' => $roleIdentifier4,
                        ],
                    ],
                ];
            }
        });

        $loader = $this->getLoaderInstance($extensionInformationProviderMock, $yamlFileLoaderMock);
        $definitions = $loader->getRoleDefinitions();

        self::assertSame(4, count($definitions->toArray()));
        self::assertTrue($definitions->offsetExists($roleIdentifier1));
        self::assertTrue($definitions->offsetExists($roleIdentifier2));
        self::assertTrue($definitions->offsetExists($roleIdentifier3));
        self::assertTrue($definitions->offsetExists($roleIdentifier4));
    }
}
