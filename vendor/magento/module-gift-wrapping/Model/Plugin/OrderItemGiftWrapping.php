<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemExtensionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;

class OrderItemGiftWrapping
{
    /**
     * @var OrderItemExtensionFactory
     */
    private $extensionFactory;

    /**
     * Init plugin
     *
     * @param OrderItemExtensionFactory $extensionFactory
     */
    public function __construct(
        OrderItemExtensionFactory $extensionFactory
    ) {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Get Gift Wrapping
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $resultEntity
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $resultEntity
    ) {

        foreach ($resultEntity->getItems() as $orderItem) {
            $this->processOrderItemGiftWrapping($orderItem);
        }

        return $resultEntity;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $resultEntity
     * @return OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        OrderRepositoryInterface $subject,
        OrderSearchResultInterface $resultEntity
    ) {
        /** @var OrderInterface $entity */
        foreach ($resultEntity->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }
        return $resultEntity;
    }

    /**
     */
    private function processOrderItemGiftWrapping(OrderItemInterface $resultEntity)
    {
        /** @var \Magento\Sales\Api\Data\OrderItemExtension $extensionAttributes */
        $extensionAttributes = $resultEntity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setGwId($resultEntity->getGwId());
        $extensionAttributes->setGwBasePrice($resultEntity->getGwBasePrice());
        $extensionAttributes->setGwPrice($resultEntity->getGwPrice());
        $extensionAttributes->setGwBaseTaxAmount($resultEntity->getGwBaseTaxAmount());
        $extensionAttributes->setGwTaxAmount($resultEntity->getGwTaxAmount());
        $extensionAttributes->setGwBasePriceInvoiced($resultEntity->getGwBasePriceInvoiced());
        $extensionAttributes->setGwPriceInvoiced($resultEntity->getGwPriceInvoiced());
        $extensionAttributes->setGwBaseTaxAmountInvoiced($resultEntity->getGwBaseTaxAmountInvoiced());
        $extensionAttributes->setGwTaxAmountInvoiced($resultEntity->getGwTaxAmountInvoiced());
        $extensionAttributes->setGwBasePriceRefunded($resultEntity->getGwBasePriceRefunded());
        $extensionAttributes->setGwPriceRefunded($resultEntity->getGwPriceRefunded());
        $extensionAttributes->setGwBaseTaxAmountRefunded($resultEntity->getGwBaseTaxAmountRefunded());
        $extensionAttributes->setGwTaxAmountRefunded($resultEntity->getGwTaxAmountRefunded());

        $resultEntity->setExtensionAttributes($extensionAttributes);
    }
}
