<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount;

class Delete extends \Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount
{
    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        // check if we know what should be deleted
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                // init model and delete
                $model = $this->_objectManager->create(\Magento\GiftCardAccount\Model\Giftcardaccount::class);
                $model->load($id);
                $model->delete();
                // display success message
                $this->messageManager->addSuccess(__('This gift card account has been deleted.'));
                // go to grid
                $this->_redirect('adminhtml/*/');
                return;
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('adminhtml/*/edit', ['id' => $id]);
                return;
            }
        }
        // display error message
        $this->messageManager->addError(__("We couldn't find a gift card account to delete."));
        // go to grid
        $this->_redirect('adminhtml/*/');
    }
}
