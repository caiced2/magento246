<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Model\Plugin;

use Magento\Framework\App\Response\RedirectInterface;
use Magento\Backend\Model\Session;
use Magento\AdminGws\Block\Adminhtml\Permissions\Tab\Rolesedit\Gws;
use Magento\User\Controller\Adminhtml\User\Role\SaveRole as ControllerAction;

/**
 * Plugin for \Magento\User\Controller\Adminhtml\User\Role\SaveRole
 */
class SaveRole
{
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $resultRedirect;

    /**
     * Request object
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Request object
     *
     * @var \Magento\Backend\Model\Session
     */
    private $backendSession;

    /**
     * SaveRole constructor.
     * @param RedirectInterface $resultRedirect
     * @param \Magento\Framework\App\RequestInterface $request
     * @param Session $backendSession
     */
    public function __construct(
        RedirectInterface $resultRedirect,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Session $backendSession
    ) {
        $this->resultRedirect = $resultRedirect;
        $this->request = $request;
        $this->backendSession = $backendSession;
    }

    /**
     * Checks if validation failed and save post data in the session
     *
     * @param ControllerAction $saveRoleController
     * @param \Magento\Backend\Model\View\Result\Redirect $result
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        ControllerAction $saveRoleController,
        $result
    ) {

        if ($this->isValidationFailed()) {
            $data = $this->request->getPostValue();
            if ($data['gws_is_all']) {
                $this->backendSession->setData(Gws::SCOPE_ALL_FORM_DATA_SESSION_KEY, $data['gws_is_all']);
            }
            if (isset($data['gws_websites'])) {
                $this->backendSession->setData(Gws::SCOPE_WEBSITE_FORM_DATA_SESSION_KEY, $data['gws_websites']);
            }
            if (isset($data['gws_store_groups'])) {
                $this->backendSession->setData(Gws::SCOPE_STORE_FORM_DATA_SESSION_KEY, $data['gws_store_groups']);
            }
        }

        return $result;
    }

    /**
     * Checks whether the validation failed
     *
     * @return bool
     */
    private function isValidationFailed(): bool
    {
        return null !== $this->backendSession->getData(ControllerAction::ROLE_EDIT_FORM_DATA_SESSION_KEY);
    }
}
