<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCatalog\Model\Indexer\Table;

use Magento\Framework\App\ObjectManager;

/**
 * Class Strategy
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @deprecated 100.2.0 logic moved to catalog module
 * @see \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy
 */
class Strategy extends \Magento\Framework\Indexer\Table\Strategy
{
    const TEMP_SUFFIX = '_temp';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy
     */
    private $tableStrategy;

    /**
     * Strategy constructor.
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy|null $tableStrategy
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy $tableStrategy = null
    ) {
        parent::__construct($resource);
        $this->tableStrategy = $tableStrategy ?: ObjectManager::getInstance()->get(
            \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy::class
        );
    }

    /**
     * Prepare index table name
     *
     * @param string $tablePrefix
     *
     * @return string
     */
    public function prepareTableName($tablePrefix)
    {
        return $this->tableStrategy->prepareTableName($tablePrefix);
    }
}
