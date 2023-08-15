<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Model\ResourceModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Indexer\CacheContext;

/**
 * Target rule resource model
 *
 * @api
 * @since 100.0.2
 */
class Rule extends \Magento\Rule\Model\ResourceModel\AbstractResource
{
    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'product' => [
            'associations_table' => 'magento_targetrule_product',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'product_id',
        ],
    ];

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var CacheContext
     */
    protected $context;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param ModuleManager $moduleManager
     * @param EventManagerInterface $eventManager
     * @param CacheContext $cacheContext
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        ModuleManager $moduleManager,
        EventManagerInterface $eventManager,
        CacheContext $cacheContext,
        $connectionName = null
    ) {
        $this->moduleManager = $moduleManager;
        $this->eventManager = $eventManager;
        $this->context = $cacheContext;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_targetrule', 'rule_id');
    }

    /**
     * Get Customer Segment Ids by rule
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return array
     */
    public function getCustomerSegmentIds(\Magento\Framework\Model\AbstractModel $object)
    {
        $ids = $this->getConnection()->select()->from(
            $this->getTable('magento_targetrule_customersegment'),
            'segment_id'
        )->where(
            'rule_id = ?',
            $object->getId()
        )->query()->fetchAll(
            \Zend_Db::FETCH_COLUMN
        );
        return empty($ids) ? [] : $ids;
    }

    /**
     * Bind rule to customer segments
     *
     * @param int $ruleId
     * @param int[] $segmentIds
     * @return $this
     */
    public function saveCustomerSegments($ruleId, $segmentIds)
    {
        if (empty($segmentIds)) {
            $segmentIds = [];
        }
        $connection = $this->getConnection();
        foreach ($segmentIds as $segmentId) {
            if (!empty($segmentId)) {
                $connection->insertOnDuplicate(
                    $this->getTable('magento_targetrule_customersegment'),
                    ['rule_id' => $ruleId, 'segment_id' => $segmentId],
                    []
                );
            }
        }

        if (empty($segmentIds)) {
            $segmentIds = [0];
        }

        $connection->delete(
            $this->getTable('magento_targetrule_customersegment'),
            ['rule_id = ?' => $ruleId, 'segment_id NOT IN (?)' => $segmentIds]
        );
        return $this;
    }

    /**
     * Add customer segment ids to rule
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setData('customer_segment_ids', $this->getCustomerSegmentIds($object));
        return parent::_afterLoad($object);
    }

    /**
     * Save matched products for current rule and clean index, clean full page cache
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\TargetRule\Model\Rule $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        $segmentIds = $object->getUseCustomerSegment() ? $object->getCustomerSegmentIds() : [0];
        $this->saveCustomerSegments($object->getId(), $segmentIds);

        return $this;
    }

    /**
     * Clean cached data by product ids
     *
     * @param array $productIds
     * @return $this
     */
    public function cleanCachedDataByProductIds($productIds)
    {
        if ($this->moduleManager->isEnabled('Magento_PageCache')) {
            $this->context->registerEntities(Product::CACHE_TAG, $productIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this->context]);
        }
        return $this;
    }

    /**
     * Clean index
     *
     * @param \Magento\Framework\Model\AbstractModel|\Magento\TargetRule\Model\Rule $object
     * @return $this
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeDelete($object);
        return $this;
    }

    /**
     * Bind specified rules to entities
     *
     * @param int[]|int|string $ruleIds
     * @param int[]|int|string $entityIds
     * @param string $entityType
     * @return $this
     */
    public function bindRuleToEntity($ruleIds, $entityIds, $entityType)
    {
        $this->_multiplyBunchInsert($ruleIds, $entityIds, $entityType);
        return $this;
    }
}
