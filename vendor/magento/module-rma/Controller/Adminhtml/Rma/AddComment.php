<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class AddComment extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Add RMA comment action
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function execute()
    {
        try {
            $this->_initModel();

            $data = $this->getRequest()->getPost('comment');
            $notify = isset($data['is_customer_notified']);
            $visible = isset($data['is_visible_on_front']);

            $rma = $this->_coreRegistry->registry('current_rma');
            if (!$rma) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid RMA'));
            }

            $comment = trim($data['comment']);
            if (!$comment) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please enter a valid message.'));
            }
            /** @var $history \Magento\Rma\Model\Rma\Status\History */
            $history = $this->_objectManager->create(\Magento\Rma\Model\Rma\Status\History::class);
            $history->setRmaEntityId($rma->getId());
            $history->setComment($comment);
            if ($notify) {
                $history->sendCommentEmail();
            }
            $history->saveComment($comment, $visible, true);

            $this->_view->loadLayout();
            $response = $this->_view->getLayout()->getBlock('comments_history')->toHtml();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We cannot add the RMA history.')];
        }
        if (is_array($response)) {
            $this->getResponse()->representJson(
                $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response)
            );
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
