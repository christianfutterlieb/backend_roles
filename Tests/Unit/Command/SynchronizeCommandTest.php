<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Tests\Unit\Command;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Command\SynchronizeCommand;
use AawTeam\BackendRoles\Role\Synchronizer;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * SynchronizeCommandTest
 */
class SynchronizeCommandTest extends UnitTestCase
{
    /**
     * @test
     */
    public function canBeInstanciated(): void
    {
        new SynchronizeCommand(
            self::createStub(LockingStrategyInterface::class),
            self::createStub(Synchronizer::class)
        );
    }
}
