<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class MassCancel extends \Magento\Invitation\Controller\Adminhtml\Index
{
    /**
     * Action for mass-cancelling invitations
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        try {
            $invitationsPost = $this->getRequest()->getParam('invitations', []);
            if (empty($invitationsPost) || !is_array($invitationsPost)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please select invitations.'));
            }
            $collection = $this->_invitationFactory->create()->getCollection()
                ->addFieldToFilter('invitation_id', ['in' => $invitationsPost])
                ->addCanBeCanceledFilter();
            $found = 0;
            $cancelled = 0;
            foreach ($collection as $invitation) {
                try {
                    $found++;
                    if ($invitation->canBeCanceled()) {
                        $invitation->cancel();
                        $cancelled++;
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    // jam all exceptions with codes
                    if (!$e->getCode()) {
                        throw $e;
                    }
                }
            }
            if ($cancelled) {
                $this->messageManager->addSuccess(__('We discarded %1 of %2 invitations.', $cancelled, $found));
            }
            $failed = $found - $cancelled;
            if ($failed) {
                $this->messageManager->addNotice(__('We skipped %1 of the selected invitations.', $failed));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('invitations/*/');
    }
}
