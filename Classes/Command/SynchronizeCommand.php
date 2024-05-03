<?php

declare(strict_types=1);

namespace AawTeam\BackendRoles\Command;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\Synchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockFactory;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * SynchronizeCommand
 */
class SynchronizeCommand extends Command
{
    protected Synchronizer $synchronizer;

    public function __construct(Synchronizer $synchronizer, string $name = null)
    {
        parent::__construct($name);
        $this->synchronizer = $synchronizer;
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure(): void
    {
        $this->setDescription('Synchronize backend_group records with their role definitions');
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Acquire lock
        try {
            $locker = $this->getLocker('backendgroups_synchronize');
            $locker->acquire();
        } catch (LockCreateException $e) {
            $output->writeln('Error: cannot create lock: ' . $e->getMessage());
            return Command::FAILURE;
        } catch (\Exception $e) {
            $output->writeln('Error: cannot acquire lock: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Run the synchronizer
        $affectedRows = $this->synchronizer->synchronizeAllBackendUserGroups();
        if ($affectedRows > 0) {
            $output->writeln('Updated ' . $affectedRows . ' backend user group(s)');
        } else {
            $output->writeln('No rows were updated: apparently, everything is already synchronized.');
        }

        // Release the lock
        $locker->release();
        return Command::SUCCESS;
    }

    protected function getLocker(string $key): LockingStrategyInterface
    {
        // @phpstan-ignore-next-line
        return GeneralUtility::makeInstance(LockFactory::class)->createLocker($key);
    }
}
