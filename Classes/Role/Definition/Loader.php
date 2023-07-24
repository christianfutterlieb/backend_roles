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
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loader
 */
final class Loader
{
    public const ROLEDEFINITIONS_BASENAME = 'BackendRoleDefinitions';

    public function __construct(
        protected readonly ExtensionInformationProvider $extensionInformationProvider,
        protected readonly DefinitionFactory $definitionFactory,
        protected readonly PhpFrontend $cache,
        protected readonly YamlFileLoader $yamlFileLoader
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

        // Define config loader functions
        // @todo: move functions to service classes
        $yamlConfigLoader = function (string $fileName): mixed {
            return $this->yamlFileLoader->load($fileName, YamlFileLoader::PROCESS_IMPORTS)['RoleDefinitions'] ?? null;
        };
        $phpConfigLoader = function (string $fileName): mixed {
            return require $fileName;
        };

        // Load from global configuration
        $globalConfigurationPath = rtrim(Environment::getConfigPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Load from config/BackendRoleDefinitions.yaml
        $defititionCollection->addFromCollection(
            $this->loadRoleDefinitionsFromFile(
                $globalConfigurationPath . self::ROLEDEFINITIONS_BASENAME . '.yaml',
                $yamlConfigLoader
            )
        );

        // Load from config/BackendRoleDefinitions.php
        $defititionCollection->addFromCollection(
            $this->loadRoleDefinitionsFromFile(
                $globalConfigurationPath . self::ROLEDEFINITIONS_BASENAME . '.php',
                $phpConfigLoader
            )
        );

        // Load from extensions
        foreach ($this->extensionInformationProvider->getLoadedExtensionListArray() as $loadedExtKey) {
            if ($loadedExtKey === 'backend_roles') {
                continue;
            }

            $extensionConfigurationPath = rtrim($this->extensionInformationProvider->extPath($loadedExtKey), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR;

            // Load from Configuration/BackendRoleDefinitions.yaml
            $defititionCollection->addFromCollection(
                $this->loadRoleDefinitionsFromFile(
                    $extensionConfigurationPath . self::ROLEDEFINITIONS_BASENAME . '.yaml',
                    $yamlConfigLoader
                )
            );

            // Load from Configuration/BackendRoleDefinitions.php
            $defititionCollection->addFromCollection(
                $this->loadRoleDefinitionsFromFile(
                    $extensionConfigurationPath . self::ROLEDEFINITIONS_BASENAME . '.php',
                    $phpConfigLoader
                )
            );
        }

        return $defititionCollection;
    }

    protected function loadRoleDefinitionsFromFile(string $roleDefinitionsFileName, callable $fileContentsReader): DefinitionCollection
    {
        $defititionCollection = new DefinitionCollection();
        if ($roleDefinitionsFileName && is_file($roleDefinitionsFileName)) {
            // Call the file content reader
            $fileContents = $fileContentsReader($roleDefinitionsFileName);

            if (!is_array($fileContents)) {
                throw new RoleDefinitionException('The role definition in "' . $roleDefinitionsFileName . '" must be array. Got ' . gettype($fileContents) . ' instead', 1589311592);
            }

            foreach ($fileContents as $roleDefinition) {
                $defititionCollection->add(
                    $this->definitionFactory->create($roleDefinition)
                );
            }
        }
        return $defititionCollection;
    }

    /**
     * Returns a reliable, reproducible and secure cache identifier.
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
