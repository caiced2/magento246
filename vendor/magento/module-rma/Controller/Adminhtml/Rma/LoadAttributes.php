<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Controller\Adminhtml\Rma;

class LoadAttributes extends \Magento\Rma\Controller\Adminhtml\Rma
{
    /**
     * Load user-defined attributes of RMA's item
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $response = false;
        $itemId = $this->getRequest()->getParam('item_id');

        try {
            $model = $this->_initModel();
            if (!$model->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The wrong RMA was requested.'));
            }
            /** @var $rma_item \Magento\Rma\Model\Item */
            $rma_item = $this->_objectManager->create(\Magento\Rma\Model\Item::class);
            if ($itemId) {
                $rma_item->load($itemId);
                if (!$rma_item->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong RMA item was requested.'));
                }
                $this->_coreRegistry->register('current_rma_item', $rma_item);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('The wrong RMA item was requested.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('We cannot display the item attributes.')];
        }

        $this->_view->loadLayout();
        $block = $this->_view->getLayout()->getBlock('magento_rma_edit_item')->initForm();
        $block->getForm()->setHtmlIdPrefix('_rma' . $itemId);
        $response = $block->toHtml();

        if (is_array($response)) {
            $this->getResponse()->representJson(
                $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($response)
            );
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
