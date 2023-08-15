<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Guest;

use Magento\Rma\Model\Rma;

class AddComment extends \Magento\Rma\Controller\Guest
{
    /**
     * Add RMA comment action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->_loadValidRma();
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }

        try {
            $response = false;
            $comment = $this->getRequest()->getPost('comment');
            $comment = trim(strip_tags($comment));

            if (!empty($comment)) {
                /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
                $statusHistory = $this->_objectManager->create(\Magento\Rma\Model\Rma\Status\History::class);
                $statusHistory->setComment($comment);
                $rma = $this->_coreRegistry->registry('current_rma');
                $statusHistory->setRmaEntityId($rma->getId());
                $statusHistory->sendCustomerCommentEmail();
                $statusHistory->saveComment($comment, true, false);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please enter a valid message.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We can\'t add a message right now.')];
        }
        if (is_array($response)) {
            $this->messageManager->addError($response['message']);
        }
        return $this->resultRedirectFactory->create()
            ->setPath('*/*/view', ['entity_id' => (int)$this->getRequest()->getParam('entity_id')]);
    }
}
