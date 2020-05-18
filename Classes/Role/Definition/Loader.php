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

            $roleDefinitionsFile = rtrim($this->extensionInformationProvider->extPath($loadedExtKey), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR . 'RoleDefinitions.php';
            if (is_file($roleDefinitionsFile) && is_readable($roleDefinitionsFile)) {
                $configRoleDefinitions = require $roleDefinitionsFile;
                if (!is_array($configRoleDefinitions)) {
                    throw new RoleDefinitionException('The role definition file "' . $roleDefinitionsFile . '" must return an array. Got ' . gettype($configRoleDefinitions) . ' instead', 1589311592);
                }

                foreach ($configRoleDefinitions as $configRoleDefinition) {
                    if (!is_array($configRoleDefinition)) {
                        throw new RoleDefinitionException('Invalid role definition found in "' . $roleDefinitionsFile . '": a role definition must be array', 1589386642);
                    }

                    if (!is_string($configRoleDefinition['identifier']) || empty($configRoleDefinition['identifier']) || trim($configRoleDefinition['identifier']) === '') {
                        throw new RoleDefinitionException('Invalid role definition found in "' . $roleDefinitionsFile . '": no or invalid identifier', 1589387779);
                    } elseif (array_key_exists($configRoleDefinition['identifier'], $roleDefinitions)) {
                        throw new RoleDefinitionException('Invalid role definition found in "' . $roleDefinitionsFile . '": the role definition identifier "' . htmlspecialchars($configRoleDefinition['identifier']) . '" already exists', 1589387862);
                    }
                    $roleDefinitions[$configRoleDefinition['identifier']] = new Definition($configRoleDefinition['identifier'], $configRoleDefinition);
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
