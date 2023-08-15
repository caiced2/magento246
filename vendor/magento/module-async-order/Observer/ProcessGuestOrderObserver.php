<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AsyncOrder\Observer;

use Magento\AsyncOrder\Api\Data\OrderInterfaceFactory as OrderFactory;
use Magento\Framework\App\DeploymentConfig;
use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\Event\ObserverInterface;

class ProcessGuestOrderObserver implements ObserverInterface
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        OrderFactory $orderFactory
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $this;
        }

        $order = $observer->getEvent()->getOrder();
        $currentCustomerId = $order->getCustomerId();
        if ($currentCustomerId === null) {
            $origOrder = $this->orderFactory->create();
            $origOrder->load($order->getEntityId());
            $origCustomerId = $origOrder->getCustomerId();
            $origOrderStatus = $origOrder->getStatus();
            if ($origCustomerId !== null && $origOrderStatus === OrderManagement::STATUS_RECEIVED) {
                $order->setCustomerId($origCustomerId);
            }
        }

        return $this;
    }
}
