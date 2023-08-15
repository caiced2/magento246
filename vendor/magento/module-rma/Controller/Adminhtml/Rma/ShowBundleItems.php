<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Controller\Adminhtml\Rma;

class ShowBundleItems extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Shows bundle items on rma create
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $response = false;
        $orderId = $this->getRequest()->getParam('order_id');
        $itemId = $this->getRequest()->getParam('item_id');

        try {
            if ($orderId && $itemId) {
                /** @var $item \Magento\Rma\Model\ResourceModel\Item */
                $item = $this->_objectManager->create(\Magento\Rma\Model\ResourceModel\Item::class);
                /** @var $items \Magento\Sales\Model\ResourceModel\Order\Item\Collection */
                $items = $item->getOrderItems($orderId, $itemId);
                if (empty($items)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('No items for bundle product'));
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The wrong order ID or item ID was requested.')
                );
            }

            $this->_coreRegistry->register('current_rma_bundle_item', $items);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We cannot display the item attributes.')];
        }

        $this->_view->loadLayout();
        $response = $this->_view->getLayout()->getBlock('magento_rma_bundle')->toHtml();

        if (is_array($response)) {
            $this->getResponse()->representJson(
                $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response)
            );
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
