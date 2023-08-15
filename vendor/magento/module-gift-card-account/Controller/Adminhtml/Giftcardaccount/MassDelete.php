<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount;

class MassDelete extends \Magento\GiftCardAccount\Controller\Adminhtml\Giftcardaccount
{
    /**
     * Delete gift card accounts specified using grid massaction
     *
     * @return void
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('giftcardaccount');
        if (!is_array($ids)) {
            $this->messageManager->addError(__('Please select a gift card account(s)'));
        } else {
            try {
                foreach ($ids as $id) {
                    $model = $this->_objectManager->create(
                        \Magento\GiftCardAccount\Model\Giftcardaccount::class
                    )->load($id);
                    $model->delete();
                }

                $this->messageManager->addSuccess(__('You deleted a total of %1 records.', count($ids)));
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('adminhtml/*/index');
    }
}
