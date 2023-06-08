<?php
/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

$EM_CONF[$_EXTKEY] = [
    'title' => 'Backend Roles',
    'description' => 'Backend user group role management for TYPO3',
    'category' => 'fe',
    'author' => 'Agentur am Wasser | Maeder & Partner AG',
    'author_email' => 'development@agenturamwasser.ch',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'version' => '2.0.0-dev',
    'constraints' => [
        'depends' => [
            'php' => '7.4.1-8.2.99',
            'typo3' => '11.5.24-11.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
