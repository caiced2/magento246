<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class CreateLabel extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Create shipping label action for specific shipment
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        try {
            $rmaModel = $this->_initModel();
            if ($this->labelService->createShippingLabel($rmaModel, $this->getRequest()->getPostValue())) {
                $rmaModel->save();
                $this->messageManager->addSuccess(__('You created a shipping label.'));
                $response->setOk(true);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response->setError(true);
            $response->setMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $response->setError(true);
            $response->setMessage(__('We can\'t create a shipping label right now.'));
        }

        $this->getResponse()->representJson($response->toJson());
        return;
    }
}
