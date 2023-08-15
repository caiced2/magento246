<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AsyncOrder\Model\ResourceModel\Order\Grid;

use Magento\AsyncOrder\Model\OrderManagement;
use Magento\Framework\App\DeploymentConfig;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

/**
 * Filter Unprocessed Async Orders in Sales/Orders Grid
 */
class Collection extends OriginalCollection
{
    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        DeploymentConfig $deploymentConfig
    ) {
        $this->deploymentConfig = $deploymentConfig;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        if ($this->deploymentConfig->get(OrderManagement::ASYNC_ORDER_OPTION_PATH)) {
            $this->addFieldToFilter('status', [
                'nin' => OrderManagement::STATUS_RECEIVED
            ]);
        }
        return $this;
    }
}
