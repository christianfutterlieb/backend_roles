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

use AawTeam\BackendRoles\Exception\RoleDefinitionException;
use AawTeam\BackendRoles\Role\Definition;
use AawTeam\BackendRoles\Role\ExtensionInformationProvider;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Loader
 */
class Loader
{
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend
     */
    protected $cache;

    /**
     * @var ExtensionInformationProvider
     */
    protected $extensionInformationProvider;

    /**
     * @param FrontendInterface $cache
     * @param ExtensionInformationProvider $extensionInformationProvider
     */
    public function __construct(FrontendInterface $cache = null, ExtensionInformationProvider $extensionInformationProvider = null)
    {
        if ($cache === null) {
            $cache = GeneralUtility::makeInstance(CacheManager::class)->getCache('backend_roles');
        }
        $this->cache = $cache;

        if ($extensionInformationProvider === null) {
            $extensionInformationProvider = GeneralUtility::makeInstance(ExtensionInformationProvider::class);
        }
        $this->extensionInformationProvider = $extensionInformationProvider;
    }

    /**
     * @return Definition[]
     */
    public function getRoleDefinitions(): array
    {
        $cacheIdentifier = $this->getRoleDefinitionCacheIdentifier();
        if ($this->cache->has($cacheIdentifier)) {
            $roleDefinitions = array_map(
                function (array $cached): Definition {
                    return new Definition($cached['identifier'], $cached);
                },
                $this->cache->require($cacheIdentifier)
            );
        } else {
            $roleDefinitions = $this->loadRoleDefinitions();
            $roleDefinitionsArray = array_map(
                function (Definition $definition): array {
                    return $definition->toArray();
                },
                $this->loadRoleDefinitions()
            );
            $this->cache->set($cacheIdentifier, 'return ' . var_export($roleDefinitionsArray, true) . ';');
        }
        return $roleDefinitions;
    }

    /**
     * @throws RoleDefinitionException
     * @return Definition[]
     */
    protected function loadRoleDefinitions(): array
    {
        $roleDefinitions = [];
        foreach ($this->extensionInformationProvider->getLoadedExtensionListArray() as $loadedExtKey) {
            if ($loadedExtKey === 'backend_roles') {
                continue;
            }

            $configFile = rtrim($this->extensionInformationProvider->extPath($loadedExtKey), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR . 'BackendUserGroupRoles.php';
            if (is_file($configFile) && is_readable($configFile)) {
                $roleDefinitionFiles = require $configFile;
                if (!is_array($roleDefinitionFiles)) {
                    throw new RoleDefinitionException('The config file "' . $configFile . '" must return an array. Got ' . gettype($roleDefinitionFiles) . ' instead.', 1589311592);
                }
                foreach ($roleDefinitionFiles as $roleDefinitionFile) {
                    if (!GeneralUtility::isAllowedAbsPath($roleDefinitionFile)) {
                        $roleDefinitionFile = GeneralUtility::getFileAbsFileName($roleDefinitionFile);
                    }
                    if (!is_file($roleDefinitionFile)) {
                        throw new RoleDefinitionException('The role definition file "' . $roleDefinitionFile . '" is not a regular file.', 1589390415);
                    } elseif (!is_readable($roleDefinitionFile)) {
                        throw new RoleDefinitionException('The role definition file "' . $roleDefinitionFile . '" is not accessible.', 1589391102);
                    }

                    $roleDefinition = require $roleDefinitionFile;
                    if (!is_array($roleDefinition)) {
                        throw new RoleDefinitionException('The role definition file "' . $roleDefinitionFile . '" must return an array. Got ' . gettype($roleDefinition) . ' instead.', 1589386642);
                    }
                    if (!is_string($roleDefinition['identifier']) || empty($roleDefinition['identifier']) || trim($roleDefinition['identifier']) === '') {
                        throw new RoleDefinitionException('The role definition file "' . $roleDefinitionFile . '" must return an array containing a not empty string offset "identifier".', 1589387779);
                    } elseif (array_key_exists($roleDefinition['identifier'], $roleDefinitions)) {
                        throw new RoleDefinitionException('The role definition identifier from file "' . $roleDefinitionFile . '" already exists.', 1589387862);
                    }
                    $roleDefinitions[$roleDefinition['identifier']] = new Definition($roleDefinition['identifier'], $roleDefinition);
                }
            }
        }
        return $roleDefinitions;
    }

    /**
     * Returns a reliable, reproducible and secure cache identifier.
     *
     * @return string
     */
    private function getRoleDefinitionCacheIdentifier(): string
    {
        return 'roleDefinitions_' . hash_hmac(
            'sha1',
            implode('-', [
                TYPO3_version,
                Environment::getProjectPath(),
                serialize(ExtensionManagementUtility::getLoadedExtensionListArray()),
            ]),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );
    }
}
