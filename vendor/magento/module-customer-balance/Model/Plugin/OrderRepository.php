<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model\Plugin;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class OrderGet
 */
class OrderRepository
{
    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionFactory
     */
    private $extensionFactory;

    /**
     * Init plugin
     *
     * @param \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->extensionFactory = $orderExtensionFactory;
    }

    /**
     * Get customer balance
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface $resultEntity
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $resultEntity
    ) {
        /** @var \Magento\Sales\Api\Data\OrderExtension $extensionAttributes */
        $extensionAttributes = $resultEntity->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $extensionAttributes->setBaseCustomerBalanceAmount($resultEntity->getBaseCustomerBalanceAmount());
        $extensionAttributes->setCustomerBalanceAmount($resultEntity->getCustomerBalanceAmount());
        
        $extensionAttributes->setBaseCustomerBalanceInvoiced($resultEntity->getBaseCustomerBalanceInvoiced());
        $extensionAttributes->setCustomerBalanceInvoiced($resultEntity->getCustomerBalanceInvoiced());
        
        $extensionAttributes->setBaseCustomerBalanceRefunded($resultEntity->getBaseCustomerBalanceRefunded());
        $extensionAttributes->setCustomerBalanceRefunded($resultEntity->getCustomerBalanceRefunded());
        
        $extensionAttributes->setBaseCustomerBalanceTotalRefunded($resultEntity->getBsCustomerBalTotalRefunded());
        $extensionAttributes->setCustomerBalanceTotalRefunded($resultEntity->getCustomerBalTotalRefunded());
        
        $resultEntity->setExtensionAttributes($extensionAttributes);

        return $resultEntity;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $resultOrder
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $resultOrder
    ) {
        /** @var OrderInterface $order */
        foreach ($resultOrder->getItems() as $order) {
            $this->afterGet($subject, $order);
        }
        return $resultOrder;
    }
}
