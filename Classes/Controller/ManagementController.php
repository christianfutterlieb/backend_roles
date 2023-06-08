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

use AawTeam\BackendRoles\Domain\Repository\BackendUserGroupRepository;
use AawTeam\BackendRoles\Role\Synchronizer;
use AawTeam\BackendRoles\Role\Definition\Formatter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * AbstractController
 */
class ManagementController extends ActionController
{
    protected BackendUserGroupRepository $backendUserGroupRepository;
    protected Synchronizer $synchronizer;
    protected Typo3Version $typo3Version;

    public function injectBackendUserGroupRepository(BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    public function injectSynchronizer(Synchronizer $synchronizer)
    {
        $this->synchronizer = $synchronizer;
    }

    public function injectTypo3Version(Typo3Version $typo3Version)
    {
        $this->typo3Version = $typo3Version;
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
    protected function indexAction(): ?ResponseInterface
    {
        $query = $this->backendUserGroupRepository->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $this->view->assign('backendUserGroups', $query->execute(true));

        // @todo: remove this construct when dropping support for TYPO3 < v11
        //        and change the return type of the method to ResponseInterface
        return $this->typo3Version->getMajorVersion() < 11
            ? null
            : $this->htmlResponse();
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
     * @param int $backendUserGroupUid
     */
    protected function exportAsRoleAction(int $backendUserGroupUid): ?ResponseInterface
    {
        $backendUserGroup = BackendUtility::getRecord('be_groups', $backendUserGroupUid);
        if (!is_array($backendUserGroup)) {
            $this->addFlashMessage('Invalid backendUserGroup UID received', 'Error', AbstractMessage::ERROR);
            $this->redirect('index');
        } elseif ($backendUserGroup['tx_backendroles_role_identifier'] ?? false) {
            $this->addFlashMessage('This BackendUserGroup is a managed group', 'Error', AbstractMessage::ERROR);
            $this->redirect('index');
        }

        $formatter = new Formatter();
        $configToExport = $formatter->formatFromDbToArray($backendUserGroup);
        $configAsString = 'return ' . ArrayUtility::arrayExport($configToExport) . ';';

        $this->view->assignMultiple([
            'backendUserGroup' => $backendUserGroup,
            'configAsString' => $configAsString,
        ]);

        // @todo: remove this construct when dropping support for TYPO3 < v11
        //        and change the return type of the method to ResponseInterface
        return $this->typo3Version->getMajorVersion() < 11
            ? null
            : $this->htmlResponse();
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
