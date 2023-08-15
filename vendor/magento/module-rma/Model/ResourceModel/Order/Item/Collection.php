<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Model\ResourceModel\Order\Item;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Psr\Log\LoggerInterface;

/**
 * Collection to show allowed order items to RMA
 */
class Collection extends \Magento\Sales\Model\ResourceModel\Order\Item\Collection
{
    /**
     * @var ConfigInterface
     */
    private $refundableList;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param Snapshot $entitySnapshot
     * @param ConfigInterface $refundableList
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        Snapshot $entitySnapshot,
        ConfigInterface $refundableList,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $entitySnapshot,
            $connection,
            $resource
        );
        $this->refundableList = $refundableList;
    }

    /**
     * Filter collection by order id
     *
     * @param int $orderId
     */
    public function filterByOrderId(int $orderId): void
    {
        $connection = $this->getConnection();
        $expression = new \Zend_Db_Expr(
            "({$connection->quoteIdentifier('qty_shipped')} - {$connection->quoteIdentifier('qty_returned')})"
        );
        $this->addExpressionFieldToSelect(
            'available_qty',
            $expression,
            ['qty_shipped', 'qty_returned']
        )->addFieldToFilter(
            'product_type',
            ['in' => $this->refundableList->filter('refundable')]
        )->addFieldToFilter(
            $expression,
            ['gt' => 0]
        );
        $this->addFieldToFilter(
            'order_id',
            $orderId
        );
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        $this->load();
        if ($this->_totalRecords === null) {
            $this->_totalRecords = count($this->getItems());
        }

        return (int)$this->_totalRecords;
    }
}
