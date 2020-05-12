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
use Nimut\TestingFramework\TestCase\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * LoaderTest
 */
class LoaderTest extends UnitTestCase
{
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp()
    {
        if (class_exists(Typo3Version::class)) {
            new Typo3Version();
        } elseif (!defined('TYPO3_version')) {
            define('TYPO3_version', '1.2.3');
        }
    }

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
        $root = vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'BackendUserGroupRoles.php' => $configurationFileContents
                ],
            ],
        ]);

        // Create /etc/shadow file (inaccessible to the application)
        $etc = vfsStream::newDirectory('etc', 0775);
        $etc->chown(vfsStream::OWNER_ROOT);
        $etc->chgrp(vfsStream::GROUP_ROOT);
        $shadow = vfsStream::newFile('shadow', 0640);
        $shadow->chown(vfsStream::OWNER_ROOT);
        $shadow->chgrp(vfsStream::GROUP_ROOT);
        $etc->addChild($shadow);
        $root->addChild($etc);

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
                '<?php return 0;',
                1589311592,
            ],
            'configuration-file-returns-float' => [
                '<?php return .0;',
                1589311592,
            ],
            'configuration-file-returns-string' => [
                '<?php return "";',
                1589311592,
            ],
            'configuration-file-returns-object' => [
                '<?php return new \\stdClass();',
                1589311592,
            ],
            // Invalid configurations
            'configuration-array-contains-not-existing-file' => [
                '<?php return ["vfs://root/some-silly-string"];',
                1589390415,
            ],
            'configuration-array-contains-dir' => [
                '<?php return ["vfs://root/etc"];',
                1589390415,
            ],
            'configuration-array-contains-inaccessible-file' => [
                '<?php return ["vfs://root/etc/shadow"];',
                1589391102,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDefinitionFileThrowsExceptionDataProvider
     * @param array $configurationDir
     * @param int $expectedExceptionCode
     */
    public function invalidDefinitionFileThrowsException(array $configurationDir, int $expectedExceptionCode)
    {
        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => $configurationDir,
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
    public function invalidDefinitionFileThrowsExceptionDataProvider(): array
    {
        return [
            // Invalid file contents (no array)
            'definition-file-returns-null' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return null;'
                ],
                1589386642,
            ],
            'definition-file-returns-bool' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return true;'
                ],
                1589386642,
            ],
            'definition-file-returns-int' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return 0;'
                ],
                1589386642,
            ],
            'definition-file-returns-float' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return .0;'
                ],
                1589386642,
            ],
            'definition-file-returns-string' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return "";'
                ],
                1589386642,
            ],
            'definition-file-returns-object' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return new \\stdClass();'
                ],
                1589386642,
            ],
            // Invalid role definition array
            'definition-file-returns-array-without-identifier' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return [];'
                ],
                1589387779,
            ],
            'definition-identifier-is-not-string' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ["identifier" => 123456789];'
                ],
                1589387779,
            ],
            'definition-identifier-is-empty-string' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ["identifier" => ""];'
                ],
                1589387779,
            ],
            'definition-identifier-contains-only-whitespaces' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ["identifier" => " \\n\\t "];'
                ],
                1589387779,
            ],
            'definition-identifier-duplicate' => [
                [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role1.php", "vfs://root/myext/Configuration/role2.php"];',
                    'role1.php' => '<?php return ["identifier" => "id"];',
                    'role2.php' => '<?php return ["identifier" => "id"];'
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
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ["identifier" => "' . $identifier . '"];',
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
    public function correctlyProcessAConfigurationAndRoleDefinition()
    {
        $identifier = 'id';
        $roleDefinitionArray = [
            'identifier' => $identifier,
        ];

        vfsStream::setup('root', null, [
            'myext' => [
                'Configuration' => [
                    'BackendUserGroupRoles.php' => '<?php return ["vfs://root/myext/Configuration/role.php"];',
                    'role.php' => '<?php return ' . var_export($roleDefinitionArray, true) . ';',
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
