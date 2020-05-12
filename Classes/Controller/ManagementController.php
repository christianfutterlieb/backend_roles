<?php
declare(strict_types=1);
namespace AawTeam\BackendRoles\Controller;

/*
 * Copyright by Agentur am Wasser | Maeder & Partner AG
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use AawTeam\BackendRoles\Role\Synchronizer;
use AawTeam\BackendRoles\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * AbstractController
 */
class ManagementController extends ActionController
{
    /**
     * @var BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var Synchronizer
     */
    protected $synchronizer;

    /**
     * @param BackendUserGroupRepository $backendUserGroupRepository
     */
    public function injectBackendUserGroupRepository(BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    /**
     * @param Synchronizer $synchronizer
     */
    public function injectSynchronizer(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
     */
    protected function initializeAction()
    {
        // Make sure only admins access this controller
        if ($this->getBackendUserAuthentication()->isAdmin() !== true) {
            $this->response->setStatus(401);
            $this->response->setContent('401 - Unauthorized');
            throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
        }
    }

    /**
     *
     */
    protected function indexAction()
    {
        $query = $this->backendUserGroupRepository->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $this->view->assign('backendUserGroups', $query->execute(true));
    }

    /**
     *
     */
    protected function synchronizeAllBackendUserGroupRolesAction()
    {
        $affectedRows = $this->synchronizer->synchronizeAllBackendUserGroups();
        if ($affectedRows > 0) {
            $this->addFlashMessage('Updated ' . $affectedRows . ' backend user group(s)');
        } else {
            $this->addFlashMessage('Apparently, everything is already synchronized.', 'No rows were updated', AbstractMessage::INFO);
        }
        $this->redirect('index');
    }

    /**
     * @param int $backendUserGroupUid
     */
    protected function resetBackendUserGroupToDefaultsAction(int $backendUserGroupUid)
    {
        $affectedRows = $this->synchronizer->resetManagedFieldsToDefaults($backendUserGroupUid);
        if ($affectedRows > 1) {
            $this->addFlashMessage('Updated ' . $affectedRows . ' backend user group(s), but it should have been only one. Please investigate this problem!', 'Something strange happened', AbstractMessage::WARNING);
        } elseif ($affectedRows > 0) {
            $this->addFlashMessage('Successfully updated the backend user group');
        } else {
            $this->addFlashMessage('Apparently, everything is already synchronized.', 'Nothing was updated', AbstractMessage::INFO);
        }
        $this->redirect('index');
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
