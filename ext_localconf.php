<?php

use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3') || die();

$bootstrap = function (): void {
    // Register cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['backend_roles'] = [
        'backend' => SimpleFileBackend::class,
        'frontend' => PhpFrontend::class,
        'groups' => [
            'system',
        ],
    ];

    // Register module icon
    /** @var IconRegistry $iconRegistry */
    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerIcon(
        'backend_roles-module-management',
        SvgIconProvider::class,
        [
            'source' => 'EXT:backend_roles/Resources/Public/Icons/ModuleManagement.svg',
        ]
    );
};
$bootstrap();
unset($bootstrap);
