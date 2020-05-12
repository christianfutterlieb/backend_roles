<?php

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

defined('TYPO3_MODE') or die();

$bootstrap = function () {
    // Register cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['backend_roles'] = [
        'backend' => \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class,
        'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
        'groups' => [
            'system',
        ],
    ];
};
$bootstrap();
unset($bootstrap);
