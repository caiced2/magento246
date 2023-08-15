<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Order attributes extension observer.
 */
class ExtendOrderAttributes implements ObserverInterface
{
    /**
     * List of attributes that should be added to an order.
     *
     * @var array
     */
    private $attributes = [
        'gw_id',
        'gw_allow_gift_receipt',
        'gw_add_card',
        'gw_price',
        'gw_base_price',
        'gw_items_price',
        'gw_items_base_price',
        'gw_card_price',
        'gw_card_base_price',
        'gw_base_tax_amount',
        'gw_tax_amount',
        'gw_items_base_tax_amount',
        'gw_items_tax_amount',
        'gw_card_base_tax_amount',
        'gw_card_tax_amount',
        'gw_price_incl_tax',
        'gw_base_price_incl_tax',
        'gw_items_price_incl_tax',
        'gw_items_base_price_incl_tax',
        'gw_card_price_incl_tax',
        'gw_card_base_price_incl_tax'
    ];

    /**
     * Performs extension of the order by the Gift Wrapping attributes.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $observer->getEvent()->getOrder();

        /* @var $quote \Magento\Quote\Model\Quote */
        $quote = $observer->getEvent()->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        foreach ($this->attributes as $attribute) {
            if ($shippingAddress->hasData($attribute)) {
                $order->setData($attribute, $shippingAddress->getData($attribute));
            }
        }
    }
}
