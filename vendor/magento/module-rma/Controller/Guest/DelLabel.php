<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Guest;

use Magento\Rma\Model\Rma;

class DelLabel extends \Magento\Rma\Controller\Guest
{
    /**
     * Delete Tracking Number action
     *
     * @return \Magento\Framework\View\Result\Layout
     */
    public function execute()
    {
        if ($this->_loadValidRma()) {
            try {
                $rma = $this->_coreRegistry->registry('current_rma');

                if (!$rma->isAvailableForPrintLabel()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Shipping Labels are not allowed.'));
                }

                $response = false;
                $number = intval($this->getRequest()->getPost('number'));

                if (empty($number)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a valid tracking number.')
                    );
                }
                /** @var $trackingNumber \Magento\Rma\Model\Shipping */
                $trackingNumber = $this->_objectManager->create(\Magento\Rma\Model\Shipping::class)->load($number);
                if ($trackingNumber->getRmaEntityId() !== $rma->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('You selected the wrong RMA.'));
                }
                $trackingNumber->delete();
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $response = ['error' => true, 'message' => $e->getMessage()];
            } catch (\Exception $e) {
                $response = ['error' => true, 'message' => __('We can\'t delete the label right now.')];
            }
        } else {
            $response = ['error' => true, 'message' => __('You selected the wrong RMA.')];
        }
        if (is_array($response)) {
            $this->_objectManager->get(
                \Magento\Framework\Session\Generic::class
            )->setErrorMessage($response['message']);
        }

        return $this->resultLayoutFactory->create();
    }
}
