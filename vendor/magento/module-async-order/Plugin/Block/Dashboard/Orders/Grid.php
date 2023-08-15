<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AsyncOrder\Plugin\Block\Dashboard\Orders;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;

/**
 * Adminhtml dashboard recent orders grid plugin
 */
class Grid
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
     * Set collection object
     *
     * @param \Magento\Backend\Block\Dashboard\Orders\Grid $subject
     * @param \Magento\Framework\Data\Collection $collection
     *
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\RuntimeException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetCollection(
        \Magento\Backend\Block\Dashboard\Orders\Grid $subject,
        \Magento\Framework\Data\Collection $collection
    ) {
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            $collection->addFieldToFilter('status', [
                'nin' => \Magento\AsyncOrder\Model\OrderManagement::STATUS_RECEIVED
            ]);
        }
    }
}
