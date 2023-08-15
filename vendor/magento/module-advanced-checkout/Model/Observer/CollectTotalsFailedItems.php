<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\Observer;

use Magento\AdvancedCheckout\Model\Cart;
use Magento\Framework\Event\ObserverInterface;

class CollectTotalsFailedItems implements ObserverInterface
{
    /**
     * @var Cart
     */
    protected $_cart;

    /**
     * @var \Magento\AdvancedCheckout\Model\FailedItemProcessor
     */
    protected $failedItemProcessor;

    /**
     * @param Cart $cart
     * @param \Magento\AdvancedCheckout\Model\FailedItemProcessor $failedItemProcessor
     * @codeCoverageIgnore
     */
    public function __construct(
        Cart $cart,
        \Magento\AdvancedCheckout\Model\FailedItemProcessor $failedItemProcessor
    ) {
        $this->_cart = $cart;
        $this->failedItemProcessor = $failedItemProcessor;
    }

    /**
     * Calculate failed items quote-related data
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $affectedItems = $this->_cart->getFailedItems();
        if (empty($affectedItems)) {
            return;
        }
        $this->failedItemProcessor->process();
    }
}
