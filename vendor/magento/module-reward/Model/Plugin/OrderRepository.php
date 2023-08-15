<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class for adding info about reward points.
 */
class OrderRepository
{
    /**
     * Check if credit memo can be created for order with reward points
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @param int $orderId
     * @return OrderInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        OrderInterface $order,
        $orderId
    ) {
        $this->addRewardInfoToExtensionAttributes($order);

        if ($order->canUnhold() || $order->isCanceled() || $order->getState() === Order::STATE_CLOSED) {
            return $order;
        }

        if ($order->getBaseRwrdCrrncyAmtInvoiced() > $order->getBaseRwrdCrrncyAmntRefnded()) {
            $order->setForcedCanCreditmemo(true);
        }

        return $order;
    }

    /**
     * Add reward points info to order.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $orderSearchResult
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $orderSearchResult
    ) {
        foreach ($orderSearchResult->getItems() as $item) {
            $this->addRewardInfoToExtensionAttributes($item);
        }

        return $orderSearchResult;
    }

    /**
     * Add info about reward points to extension attributes.
     *
     * @param OrderInterface $order
     * @return void
     */
    private function addRewardInfoToExtensionAttributes(OrderInterface $order)
    {
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes->setRewardPointsBalance($order->getData('reward_points_balance'));
        $extensionAttributes->setRewardCurrencyAmount($order->getData('reward_currency_amount'));
        $extensionAttributes->setBaseRewardCurrencyAmount($order->getData('base_reward_currency_amount'));
        $order->setExtensionAttributes($extensionAttributes);
    }
}
