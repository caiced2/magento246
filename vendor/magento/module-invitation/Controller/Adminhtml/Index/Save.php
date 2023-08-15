<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Invitation\Controller\Adminhtml\Index
{
    /**
     * Create & send new invitations
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            // parse POST data
            if (!$this->getRequest()->isPost()) {
                return $resultRedirect->setPath('invitations/*/');
            }

            $this->_getSession()->setInvitationFormData($this->getRequest()->getPostValue());
            $emails = preg_split('/\s+/s', $this->getRequest()->getParam('email'));
            foreach ($emails as $key => $email) {
                $email = trim($email);
                if (empty($email)) {
                    unset($emails[$key]);
                } else {
                    $emails[$key] = $email;
                }
            }
            if (empty($emails)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Please specify at least one email address.')
                );
            }
            if ($this->_storeManager->hasSingleStore()) {
                $storeId = $this->_storeManager->getStore(true)->getId();
            } else {
                $storeId = $this->getRequest()->getParam('store_id');
            }

            // try to send invitation(s)
            $sentCount = 0;
            $failedCount = 0;
            $customerExistsCount = 0;
            foreach ($emails as $key => $email) {
                try {
                    /** @var \Magento\Invitation\Model\Invitation $invitation */
                    $invitation = $this->_invitationFactory->create()->setData(
                        [
                            'email' => $email,
                            'store_id' => $storeId,
                            'message' => $this->getRequest()->getParam('message'),
                            'group_id' => $this->getRequest()->getParam('group_id'),
                        ]
                    )->save();
                    if ($invitation->sendInvitationEmail()) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Magento\Framework\Exception\InputException $e) {
                    $failedCount++;
                } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                    $failedCount++;
                    $customerExistsCount++;
                }
            }
            if ($sentCount) {
                $this->messageManager->addSuccess(__('We sent %1 invitation(s).', $sentCount));
            }
            if ($failedCount) {
                $this->messageManager->addError(
                    __('Something went wrong while sending %1 of %2 invitations.', $failedCount, count($emails))
                );
            }
            if ($customerExistsCount) {
                $this->messageManager->addNotice(
                    __(
                        '%1 invitation(s) were not sent, '
                        . 'because customer accounts already exist for these email addresses.',
                        $customerExistsCount
                    )
                );
            }
            $this->_getSession()->unsInvitationFormData();
            return $resultRedirect->setPath('invitations/*/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('invitations/*/new');
    }
}
