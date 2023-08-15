<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class SaveShipping extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Save shipment
     * We can save only new shipment. Existing shipments are not editable
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $responseAjax = new \Magento\Framework\DataObject();

        try {
            $model = $this->_initModel();
            if ($model) {
                if ($this->labelService->createShippingLabel($model, $this->getRequest()->getPostValue())) {
                    $this->messageManager->addSuccess(__('You created a shipping label.'));
                    $responseAjax->setOk(true);
                }
                $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getCommentText(true);
            } else {
                $this->_forward('noroute');
                return;
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $responseAjax->setError(true);
            $responseAjax->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $responseAjax->setError(true);
            $responseAjax->setMessage(__('We can\'t create a shipping label right now.'));
        }
        $this->getResponse()->representJson($responseAjax->toJson());
    }
}
