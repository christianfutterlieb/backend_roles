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
use AawTeam\BackendRoles\Role\DefinitionCollection;
use AawTeam\BackendRoles\Role\DefinitionFactory;
use AawTeam\BackendRoles\Role\ExtensionInformationProvider;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loader
 */
final class Loader
{
    public function __construct(
        protected readonly ExtensionInformationProvider $extensionInformationProvider,
        protected readonly DefinitionFactory $definitionFactory,
        protected readonly PhpFrontend $cache
    ) {
    }

    public function getRoleDefinitions(): DefinitionCollection
    {
        $cacheIdentifier = $this->getRoleDefinitionCacheIdentifier();
        if ($this->cache->has($cacheIdentifier)) {
            $roleDefinitions = new DefinitionCollection();
            array_walk($this->cache->require($cacheIdentifier), function (array $definitionArray) use (&$roleDefinitions) {
                $roleDefinitions->add(
                    $this->definitionFactory->create($definitionArray)
                );
            });
        } else {
            $roleDefinitions = $this->loadRoleDefinitions();
            $roleDefinitionsArray = array_map(
                function (Definition $definition): array {
                    return $definition->toArray();
                },
                $roleDefinitions->toArray()
            );
            $this->cache->set($cacheIdentifier, 'return ' . var_export($roleDefinitionsArray, true) . ';');
        }
        return $roleDefinitions;
    }

    protected function loadRoleDefinitions(): DefinitionCollection
    {
        $defititionCollection = new DefinitionCollection();
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
                    $defititionCollection->add(
                        $this->definitionFactory->create($configRoleDefinition)
                    );
                }
            }
        }

        return $defititionCollection;
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
                GeneralUtility::makeInstance(Typo3Version::class)->getBranch(),
                Environment::getProjectPath(),
                serialize($this->extensionInformationProvider->getLoadedExtensionListArray()),
            ]),
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']
        );
    }
}
