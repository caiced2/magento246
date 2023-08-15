<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\App\ResourceConnection;

/**
 * Class Full
 */
class Full extends \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\AbstractAction
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection
     * @param \Magento\SalesRule\Model\RuleFactory $ruleFactory
     * @param \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel
     * @param Generator|null $generator
     * @param ResourceConnection|null $resourceConnection
     */
    public function __construct(
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\AdvancedSalesRule\Model\ResourceModel\Rule\Condition\Filter $filterResourceModel,
        Generator $generator = null,
        ResourceConnection $resourceConnection = null
    ) {
        parent::__construct($ruleCollection, $ruleFactory, $filterResourceModel);
        $this->generator = $generator ?: ObjectManager::getInstance()->get(Generator::class);
        $this->resourceConnection = $resourceConnection ?: ObjectManager::getInstance()->get(ResourceConnection::class);
    }

    /**
     * Refresh entities index
     *
     * @return $this
     */
    public function execute()
    {
        $connection = $this->resourceConnection->getConnection();
        $batchSelectIterator = $this->generator->generate(
            'rule_id',
            $connection->select()
                ->from($this->resourceConnection->getTableName('salesrule'), 'rule_id'),
            1000,
            \Magento\Framework\DB\Query\BatchIteratorInterface::NON_UNIQUE_FIELD_ITERATOR
        );

        foreach ($batchSelectIterator as $select) {
            $this->setActionIds($connection->fetchCol($select));
            $this->reindex();
        }

        return $this;
    }
}
