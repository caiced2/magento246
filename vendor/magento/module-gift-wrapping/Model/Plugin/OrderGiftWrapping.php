<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

class OrderGiftWrapping
{
    /**
     * @var OrderExtensionFactory
     */
    private $orderExtensionFactory;

    /**
     * Init plugin
     *
     * @param OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
    }

    /**
     * Get Gift Wrapping
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        /** @var \Magento\Sales\Api\Data\OrderExtension $extensionAttributes */
        $extensionAttributes = $order->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->orderExtensionFactory->create();
        }

        $extensionAttributes->setGwId($order->getGwId());
        $extensionAttributes->setGwAllowGiftReceipt($order->getGwAllowGiftReceipt());
        $extensionAttributes->setGwAddCard($order->getGwAddCard());
        $extensionAttributes->setGwBasePrice($order->getGwBasePrice());
        $extensionAttributes->setGwPrice($order->getGwPrice());
        $extensionAttributes->setGwItemsBasePrice($order->getGwItemsBasePrice());
        $extensionAttributes->setGwItemsPrice($order->getGwItemsPrice());
        $extensionAttributes->setGwCardBasePrice($order->getGwCardBasePrice());
        $extensionAttributes->setGwCardPrice($order->getGwCardPrice());
        $extensionAttributes->setGwBaseTaxAmount($order->getGwBaseTaxAmount());
        $extensionAttributes->setGwTaxAmount($order->getGwTaxAmount());
        $extensionAttributes->setGwItemsBaseTaxAmount($order->getGwItemsBaseTaxAmount());
        $extensionAttributes->setGwItemsTaxAmount($order->getGwItemsTaxAmount());
        $extensionAttributes->setGwCardBaseTaxAmount($order->getGwCardBaseTaxAmount());
        $extensionAttributes->setGwCardTaxAmount($order->getGwCardTaxAmount());
        $extensionAttributes->setGwBasePriceInclTax($order->getGwBasePriceInclTax());
        $extensionAttributes->setGwPriceInclTax($order->getGwPriceInclTax());
        $extensionAttributes->setGwItemsBasePriceInclTax($order->getGwItemsBasePriceInclTax());
        $extensionAttributes->setGwItemsPriceInclTax($order->getGwItemsPriceInclTax());
        $extensionAttributes->setGwCardBasePriceInclTax($order->getGwCardBasePriceInclTax());
        $extensionAttributes->setGwCardPriceInclTax($order->getGwCardPriceInclTax());
        $extensionAttributes->setGwBasePriceInvoiced($order->getGwBasePriceInvoiced());
        $extensionAttributes->setGwPriceInvoiced($order->getGwPriceInvoiced());
        $extensionAttributes->setGwItemsBasePriceInvoiced($order->getGwItemsBasePriceInvoiced());
        $extensionAttributes->setGwItemsPriceInvoiced($order->getGwItemsPriceInvoiced());
        $extensionAttributes->setGwCardBasePriceInvoiced($order->getGwCardBasePriceInvoiced());
        $extensionAttributes->setGwCardPriceInvoiced($order->getGwCardPriceInvoiced());
        $extensionAttributes->setGwBaseTaxAmountInvoiced($order->getGwBaseTaxAmountInvoiced());
        $extensionAttributes->setGwTaxAmountInvoiced($order->getGwTaxAmountInvoiced());
        $extensionAttributes->setGwItemsBaseTaxInvoiced($order->getGwItemsBaseTaxInvoiced());
        $extensionAttributes->setGwItemsTaxInvoiced($order->getGwItemsTaxInvoiced());
        $extensionAttributes->setGwCardBaseTaxInvoiced($order->getGwCardBaseTaxInvoiced());
        $extensionAttributes->setGwCardTaxInvoiced($order->getGwCardTaxInvoiced());
        $extensionAttributes->setGwBasePriceRefunded($order->getGwBasePriceRefunded());
        $extensionAttributes->setGwPriceRefunded($order->getGwPriceRefunded());
        $extensionAttributes->setGwItemsBasePriceRefunded($order->getGwItemsBasePriceRefunded());
        $extensionAttributes->setGwItemsPriceRefunded($order->getGwItemsPriceRefunded());
        $extensionAttributes->setGwCardBasePriceRefunded($order->getGwCardBasePriceRefunded());
        $extensionAttributes->setGwCardPriceRefunded($order->getGwCardPriceRefunded());
        $extensionAttributes->setGwBaseTaxAmountRefunded($order->getGwBaseTaxAmountRefunded());
        $extensionAttributes->setGwTaxAmountRefunded($order->getGwTaxAmountRefunded());
        $extensionAttributes->setGwItemsBaseTaxRefunded($order->getGwItemsBaseTaxRefunded());
        $extensionAttributes->setGwItemsTaxRefunded($order->getGwItemsTaxRefunded());
        $extensionAttributes->setGwCardBaseTaxRefunded($order->getGwCardBaseTaxRefunded());
        $extensionAttributes->setGwCardTaxRefunded($order->getGwCardTaxRefunded());

        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $orderSearchResult
     * @return OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $orderSearchResult
    ) {
        /** @var OrderInterface $entity */
        foreach ($orderSearchResult->getItems() as $order) {
            $this->afterGet($subject, $order);
        }
        return $orderSearchResult;
    }
}
