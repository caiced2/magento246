<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

/**
 * Remove all failed items action.
 */
class RemoveAllFailed extends \Magento\AdvancedCheckout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * Remove all failed items from storage
     *
     * @return void
     */
    public function execute()
    {
        $this->_getFailedItemsCart()->removeAllAffectedItems();
        $this->messageManager->addSuccess(__('You removed the items.'));
        $this->_redirect('checkout/cart');
    }
}
