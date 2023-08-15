<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MassResend extends \Magento\Invitation\Controller\Adminhtml\Index
{
    /**
     * Action for mass-resending invitations
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        try {
            $resultRedirect = $this->resultRedirectFactory->create();
            $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
            $isPost = $this->getRequest()->isPost();
            if (!$formKeyIsValid || !$isPost) {
                $this->messageManager->addError(__('Something went wrong while sending invitations.'));
                return $resultRedirect->setPath('invitations/*/');
            }

            $invitationsPost = $this->getRequest()->getParam('invitations', []);
            if (empty($invitationsPost) || !is_array($invitationsPost)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please select invitations.'));
            }
            $collection = $this->_invitationFactory->create()->getCollection()
                ->addFieldToFilter('invitation_id', ['in' => $invitationsPost])
                ->addCanBeSentFilter();
            $found = 0;
            $sent = 0;
            $customerExists = 0;
            /** @var \Magento\Invitation\Model\Invitation $invitation */
            foreach ($collection as $invitation) {
                try {
                    $invitation->makeSureCanBeSent();
                    $found++;
                    if ($invitation->sendInvitationEmail()) {
                        $sent++;
                    }
                } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                    $customerExists++;
                    $invitation->cancel();
                }
            }
            if (!$found) {
                $this->messageManager->addError(__('No invitations have been resent.'));
            }
            if ($sent) {
                $this->messageManager->addSuccess(__('You sent %1 of %2 invitation(s).', $sent, $found));
            }
            $failed = $found - $sent;
            if ($failed) {
                $this->messageManager->addError(__('Something went wrong while sending %1 invitations.', $failed));
            }
            if ($customerExists) {
                $this->messageManager->addNotice(
                    __('We discarded %1 invitation(s) addressed to current customers.', $customerExists)
                );
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('invitations/*/');
    }
}
