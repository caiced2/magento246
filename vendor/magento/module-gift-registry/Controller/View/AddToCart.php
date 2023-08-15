<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\View;

class AddToCart extends \Magento\GiftRegistry\Controller\View
{
    /**
     * Add specified gift registry items to quote
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $items = $this->getRequest()->getParam('items');
        if (!$items) {
            $this->_redirect('*/*', ['_current' => true]);
            return;
        }
        /* @var \Magento\Checkout\Model\Cart */
        $cart = $this->_objectManager->get(\Magento\Checkout\Model\Cart::class);

        $success = false;

        try {
            $count = 0;
            foreach ($items as $itemId => $itemInfo) {
                $item = $this->_objectManager->create(\Magento\GiftRegistry\Model\Item::class)->load($itemId);
                $optionCollection = $this->_objectManager->create(
                    \Magento\GiftRegistry\Model\Item\Option::class
                )->getCollection()->addItemFilter(
                    $itemId
                );
                $item->setOptions($optionCollection->getOptionsByItem($item));
                if (!$item->getId() || $itemInfo['qty'] < 1 || $item->getQty() <= $item->getQtyFulfilled()) {
                    continue;
                }
                $item->addToCart($cart, $itemInfo['qty']);
                $count += $itemInfo['qty'];
            }
            $cart->save()->getQuote()->collectTotals();
            $success = true;
            if (!$count) {
                $success = false;
                $this->messageManager->addError(__('Please enter the quantity of items to add to cart.'));
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(__($e->getMessage()));
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
        }
        if (!$success) {
            $this->_redirect('*/*', ['_current' => true]);
        } else {
            $this->_redirect('checkout/cart');
        }
    }
}
