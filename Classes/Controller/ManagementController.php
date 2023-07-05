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
use AawTeam\BackendRoles\Role\Definition\Formatter;
use AawTeam\BackendRoles\Role\Synchronizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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
    protected ModuleTemplate $moduleTemplate;

    public function __construct(
        protected readonly BackendUserGroupRepository $backendUserGroupRepository,
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly Synchronizer $synchronizer,
        protected readonly Typo3Version $typo3Version
    ) {
    }

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
     */
    protected function initializeAction()
    {
        // Make sure only admins access this controller
        if ($this->getBackendUserAuthentication()->isAdmin() !== true) {
            throw new PropagateResponseException(
                $this->responseFactory->createResponse(401)
            );
        }

        // Initialize the ModuleTemplate instance
        $this->moduleTemplate = $this->setupModuleTemplate(
            $this->iconFactory,
            $this->moduleTemplateFactory,
            $this->request
        );
    }

    protected function setupModuleTemplate(
        IconFactory $iconFactory,
        ModuleTemplateFactory $moduleTemplateFactory,
        ServerRequestInterface $request
    ): ModuleTemplate {
        // Create
        $moduleTemplate = $moduleTemplateFactory->create($request);

        // Add page zero as metaInformation
        $moduleTemplate->getDocHeaderComponent()->setMetaInformation([
            'uid' => 0,
        ]);

        // Add a refresh button
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $refreshButton = $buttonBar->makeLinkButton()
            ->setTitle('Refresh this page')
            ->setHref((string)$request->getUri())
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $moduleTemplate;
    }

    protected function initializeIndexAction(): void
    {
        // Generate Buttons for this action
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Add the Shortcut button
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName('Shortcut')
            ->setRouteIdentifier('system_BackendRolesManagement.Management_index');
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
    }

    protected function indexAction(): ResponseInterface
    {
        $query = $this->backendUserGroupRepository->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->setOrderings(['title' => QueryInterface::ORDER_ASCENDING]);
        $this->view->assign('backendUserGroups', $query->execute(true));

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    protected function synchronizeAllBackendUserGroupRolesAction(): ResponseInterface
    {
        $affectedRows = $this->synchronizer->synchronizeAllBackendUserGroups();
        if ($affectedRows > 0) {
            $this->addFlashMessage('Updated ' . $affectedRows . ' backend user group(s)');
        } else {
            $this->addFlashMessage('Apparently, everything is already synchronized.', 'No rows were updated', AbstractMessage::INFO);
        }
        return $this->redirect('index');
    }

    /**
     * @param int $backendUserGroupUid
     */
    protected function resetBackendUserGroupToDefaultsAction(int $backendUserGroupUid): ResponseInterface
    {
        $affectedRows = $this->synchronizer->resetManagedFieldsToDefaults($backendUserGroupUid);
        if ($affectedRows > 1) {
            $this->addFlashMessage('Updated ' . $affectedRows . ' backend user group(s), but it should have been only one. Please investigate this problem!', 'Something strange happened', AbstractMessage::WARNING);
        } elseif ($affectedRows > 0) {
            $this->addFlashMessage('Successfully updated the backend user group');
        } else {
            $this->addFlashMessage('Apparently, everything is already synchronized.', 'Nothing was updated', AbstractMessage::INFO);
        }
        return $this->redirect('index');
    }

    /**
     * @param int $backendUserGroupUid
     */
    protected function exportAsRoleAction(int $backendUserGroupUid): ResponseInterface
    {
        $backendUserGroup = BackendUtility::getRecord('be_groups', $backendUserGroupUid);
        if (!is_array($backendUserGroup)) {
            $this->addFlashMessage('Invalid backendUserGroup UID received', 'Error', AbstractMessage::ERROR);
            return $this->redirect('index');
        }
        if ($backendUserGroup['tx_backendroles_role_identifier'] ?? false) {
            $this->addFlashMessage('This BackendUserGroup is a managed group', 'Error', AbstractMessage::ERROR);
            return $this->redirect('index');
        }

        $formatter = new Formatter();
        $configToExport = $formatter->formatFromDbToArray($backendUserGroup);
        $configAsString = 'return ' . ArrayUtility::arrayExport($configToExport) . ';';

        $this->view->assignMultiple([
            'backendUserGroup' => $backendUserGroup,
            'configAsString' => $configAsString,
        ]);

        // Extend ModuleTemplate for this action
        $this->extendModuleTemplateForExportAsRoleAction();

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    protected function extendModuleTemplateForExportAsRoleAction(): void
    {
        // Generate Buttons for this action
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Add the Shortcut button
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setDisplayName('Shortcut')
            ->setRouteIdentifier('system_BackendRolesManagement.Management_exportAsRole')
            ->setArguments([
                'backendUserGroupUid' => $this->arguments->getArgument('backendUserGroupUid')->getValue(),
            ]);
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Add a 'close' button leading to indexAction()
        $closeButton = $buttonBar->makeLinkButton()
            ->setTitle('Close')
            ->setShowLabelText(true)
            ->setHref($this->uriBuilder->reset()->uriFor('index'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
