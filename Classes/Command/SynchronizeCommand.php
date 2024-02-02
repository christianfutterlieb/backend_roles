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
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;

/**
 * SynchronizeCommand
 */
class SynchronizeCommand extends Command
{
    public function __construct(
        protected readonly LockingStrategyInterface $locker,
        protected readonly Synchronizer $synchronizer,
        string $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this->setDescription('Synchronize backend_group records with their role definitions');
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Acquire lock
        try {
            $this->locker->acquire();
        } catch (\TYPO3\CMS\Core\Locking\Exception\LockCreateException $e) {
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
        $this->locker->release();
        return Command::SUCCESS;
    }
}
