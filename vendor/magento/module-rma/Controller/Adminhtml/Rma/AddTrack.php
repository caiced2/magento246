<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class AddTrack extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Add new tracking number action
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number = $this->getRequest()->getPost('number');
            $title = $this->getRequest()->getPost('title');
            if (empty($carrier)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please specify a carrier.'));
            }
            if (empty($number)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please enter a tracking number.'));
            }

            $model = $this->_initModel();
            if ($model->getId()) {
                $this->labelService->addTrack($model->getId(), $number, $carrier, $title);
                $this->_view->loadLayout();
                $response = $this->_view->getLayout()->getBlock('shipment_tracking')->toHtml();
            } else {
                $response = [
                    'error' => true,
                    'message' => __('We cannot initialize an RMA to add a tracking number.'),
                ];
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = [
                'error'     => true,
                'message'   => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We can\'t add a message right now.')];
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
