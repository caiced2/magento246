<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\AsyncOrder\Plugin\Model;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Sales\Model\OrderIncrementIdChecker;
use Magento\Sales\Model\ResourceModel\Order;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * After plugin for isIncrementIdUsed to return false if async order is enabled.
 */
class OrderIncrementIdCheckerPlugin
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @var Order
     */
    private $resourceModel;

    /**
     * @param DeploymentConfig $deploymentConfig
     * @param Order $resourceModel
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        Order $resourceModel
    ) {
        $this->deploymentConfig = $deploymentConfig;
        $this->resourceModel = $resourceModel;
    }

    /**
     * Do not increment id if order status is "received" or if there is no status.
     *
     * @param OrderIncrementIdChecker $subject
     * @param bool $result
     * @param string|int $orderIncrementId
     * @return bool
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\RuntimeException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterIsIncrementIdUsed(
        OrderIncrementIdChecker $subject,
        $result,
        $orderIncrementId
    ) {
        if (!$this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            return $result;
        }

        $adapter = $this->resourceModel->getConnection();
        $bind = [':increment_id' => $orderIncrementId];
        /** @var \Magento\Framework\DB\Select $select */
        $select = $adapter->select();
        $select->from($this->resourceModel->getMainTable(), OrderInterface::STATUS)
            ->where('increment_id = :increment_id');
        $status = $adapter->fetchOne($select, $bind);

        return !($status === OrderManagement::STATUS_RECEIVED || !$status);
    }
}
