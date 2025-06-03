<?php

declare(strict_types=1);

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'backend_roles-module-management' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:backend_roles/Resources/Public/Icons/ModuleManagement.svg',
    ],
];
