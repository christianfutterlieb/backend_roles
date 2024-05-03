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
use Symfony\Component\Yaml\Yaml;
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
    ) {}

    /**
     * {@inheritDoc}
     * @see \TYPO3\CMS\Extbase\Mvc\Controller\ActionController::initializeAction()
     */
    protected function initializeAction(): void
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

    protected function exportAsRoleAction(int $backendUserGroupUid): ResponseInterface
    {
        try {
            $backendUserGroup = $this->getUnmanagedBackendUserGroupRecord($backendUserGroupUid);
        } catch (\InvalidArgumentException $e) {
            $this->addFlashMessage($e->getMessage(), 'Error', AbstractMessage::ERROR);
            return $this->redirect('index');
        }

        $configToExport = $this->createConfigToExportFromBackendUserGroup($backendUserGroup);

        $this->view->assignMultiple([
            'backendUserGroup' => $backendUserGroup,
            'yamlConfigAsString' => Yaml::dump(['RoleDefinitions' => [$configToExport]], 10, 2),
            'phpConfigAsString' => 'return ' . ArrayUtility::arrayExport([$configToExport]) . ';',
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

    protected function downloadRoleDefinitionAction(int $backendUserGroupUid, string $fileFormat): ResponseInterface
    {
        try {
            $backendUserGroup = $this->getUnmanagedBackendUserGroupRecord($backendUserGroupUid);
        } catch (\InvalidArgumentException $e) {
            $this->addFlashMessage($e->getMessage(), 'Error', AbstractMessage::ERROR);
            return $this->redirect('index');
        }

        if (!in_array($fileFormat, ['yaml', 'php'], true)) {
            $this->addFlashMessage('Invalid fileFormat', 'Error', AbstractMessage::ERROR);
            return $this->redirect('index');
        }

        $configToExport = $this->createConfigToExportFromBackendUserGroup($backendUserGroup);

        if ($fileFormat === 'yaml') {
            $configAsString = Yaml::dump(['RoleDefinitions' => [$configToExport]], 99, 2) . PHP_EOL;
            $fileName = 'role-' . $backendUserGroupUid . '.yaml';
            // @see https://www.iana.org/assignments/media-types/application/yaml
            $mimeType = 'application/yaml';
        } elseif ($fileFormat === 'php') {
            $configAsString = '<?php' . PHP_EOL . PHP_EOL . 'return ' . ArrayUtility::arrayExport($configToExport) . ';' . PHP_EOL;
            $fileName = 'role-' . $backendUserGroupUid . '.php';
            $mimeType = 'text/x-php';
        }

        $body = $this->streamFactory->createStream($configAsString);
        return $this->responseFactory->createResponse()
            ->withBody($body)
            ->withHeader('Content-Length', (string)$body->getSize())
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Pragma', 'no-cache')
        ;
    }

    /**
     * @param mixed[] $backendUserGroup
     * @return mixed[]
     */
    private function createConfigToExportFromBackendUserGroup(array $backendUserGroup): array
    {
        // Add a template for identifier and title
        return array_merge(
            [
                'identifier' => 'PUT_THE_IDENTIFIER_HERE',
                'title' => '[Role] ' . $backendUserGroup['title'],
            ],
            (new Formatter())->formatFromDbToArray($backendUserGroup)
        );
    }

    /**
     * @return mixed[]
     */
    private function getUnmanagedBackendUserGroupRecord(int $backendUserGroupUid): array
    {
        $backendUserGroup = BackendUtility::getRecord('be_groups', $backendUserGroupUid);
        if (!is_array($backendUserGroup)) {
            throw new \InvalidArgumentException('Invalid backendUserGroup UID received');
        }
        if ($backendUserGroup['tx_backendroles_role_identifier'] ?? false) {
            throw new \InvalidArgumentException('This BackendUserGroup is a managed group');
        }
        return $backendUserGroup;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
