<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\Controller\ResultFactory;

class SaveInvitation extends \Magento\Invitation\Controller\Adminhtml\Index
{
    /**
     * Edit invitation's information
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $invitation = $this->_initInvitation();

            if ($this->getRequest()->isPost()) {
                $email = $this->getRequest()->getParam('email');

                $invitation->setMessage($this->getRequest()->getParam('message'))->setEmail($email);

                $result = $invitation->validate();
                //checking if there was validation
                if (is_array($result) && !empty($result)) {
                    foreach ($result as $message) {
                        $this->messageManager->addError($message);
                    }
                    return $resultRedirect->setPath('invitations/*/view', ['_current' => true]);
                }

                //If there was no validation errors trying to save
                $invitation->save();

                $this->messageManager->addSuccess(__('The invitation has been saved.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('invitations/*/view', ['_current' => true]);
    }
}
