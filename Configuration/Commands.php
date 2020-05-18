<?php
declare(strict_types = 1);

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

// @todo Remove this file when dropping support for TYPO3 < v10.3
// @see https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.3/Feature-89139-AddDependencyInjectionSupportForConsoleCommands.html
return [
    'backendroles:synchronize' => [
        'class' => \AawTeam\BackendRoles\Command\SynchronizeCommand::class,
        'schedulable' => false,
    ],
];
