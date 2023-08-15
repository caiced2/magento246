<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Model\ResourceModel\Plugin;

use Magento\Sales\Model\ResourceModel\GridPool as GridPoolResource;
use Magento\SalesArchive\Model\ResourceModel\Archive as ArchiveResource;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Grid as GridResource;

/**
 * Plugin for Magento\Sales\Model\ResourceModel\Grid
 */
class Grid
{
    /**
     * @var GridPoolResource
     */
    private $gridPoolResource;

    /**
     * @var ArchiveResource
     */
    private $archiveResource;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param GridPoolResource $gridPoolResource
     * @param ArchiveResource $archiveResource
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GridPoolResource $gridPoolResource,
        ArchiveResource $archiveResource,
        ResourceConnection $resourceConnection
    ) {
        $this->gridPoolResource = $gridPoolResource;
        $this->archiveResource = $archiveResource;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Removes order from archive and refreshes grids
     *
     * @param GridResource $subject
     * @param string $value
     * @param string|null $field
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeRefresh(GridResource $subject, $value, $field = null)
    {
        if ($subject->getGridTable() == $this->resourceConnection->getTableName('sales_order')
            && $this->archiveResource->isOrderInArchive($value)
        ) {
            $this->archiveResource->removeOrdersFromArchiveById([$value]);
            $this->gridPoolResource->refreshByOrderId($value);
        }
    }
}
