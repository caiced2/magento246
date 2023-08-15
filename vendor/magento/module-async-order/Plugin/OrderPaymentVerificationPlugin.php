<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Payment\Observer\SalesOrderBeforeSaveObserver;

/**
 * Order payment verification plugin for registering customer after initial async checkout.
 */
class OrderPaymentVerificationPlugin
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Skip order payment verification if it's initial order in "received" status.
     *
     * @param SalesOrderBeforeSaveObserver $subject
     * @param \Closure $proceed
     * @param Observer $observer
     * @return Observer
     * @throws FileSystemException
     * @throws RuntimeException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        SalesOrderBeforeSaveObserver $subject,
        \Closure $proceed,
        Observer $observer
    ) {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order->getStatus() === OrderManagement::STATUS_RECEIVED) {
                return $observer;
            }
        }

        return $proceed($observer);
    }
}
