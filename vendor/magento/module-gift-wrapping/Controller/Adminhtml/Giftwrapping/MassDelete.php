<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping;

use Magento\Framework\Controller\ResultFactory;

class MassDelete extends \Magento\GiftWrapping\Controller\Adminhtml\Giftwrapping
{
    /**
     * Delete specified gift wrapping(s)
     * This action can be performed on 'Manage Gift Wrappings' page
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $wrappingIds = (array)$this->getRequest()->getParam('wrapping_ids');
        if (!is_array($wrappingIds)) {
            $this->messageManager->addError(__('An item needs to be selected. Select and try again.'));
        } else {
            try {
                $wrappingCollection = $this->_objectManager->create(
                    \Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection::class
                );
                $wrappingCollection->addFieldToFilter('wrapping_id', ['in' => $wrappingIds]);
                foreach ($wrappingCollection as $wrapping) {
                    $wrapping->delete();
                }
                $this->messageManager->addSuccess(__('You deleted a total of %1 records.', count($wrappingIds)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('adminhtml/*/index');
        return $resultRedirect;
    }
}
