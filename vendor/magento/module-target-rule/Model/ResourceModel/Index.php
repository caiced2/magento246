<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\ResourceModel;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Customer\Model\Session;
use Magento\CustomerSegment\Helper\Data as CustomerSegmentHelper;
use Magento\CustomerSegment\Model\Customer;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TargetRule\Helper\Data as TargetRuleHelper;
use Magento\TargetRule\Model\Index as TargetRuleIndex;
use Magento\TargetRule\Model\ResourceModel\Rule as ResourceModelRule;
use Magento\TargetRule\Model\Rule;

/**
 * TargetRule Product Index by Rule Product List Type Resource Model
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Index extends AbstractDb
{
    /**
     * Increment value for generate unique bind names
     *
     * @var int
     */
    protected $_bindIncrement = 0;

    /**
     * @var TargetRuleHelper
     */
    protected $_targetRuleData;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var CustomerSegmentHelper
     */
    protected $_customerSegmentData;

    /**
     * @var Session
     */
    protected $_session;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * @var Visibility
     */
    protected $_visibility;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Segment
     */
    protected $_segmentCollectionFactory;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var ResourceModelRule
     */
    protected $_rule;

    /**
     * @var IndexPool
     */
    protected $_indexPool;

    /**
     * @var Stock
     */
    private $stockHelper;

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var int
     */
    private $defaultSegmentId = 0;

    /**
     * @param Context $context
     * @param IndexPool $indexPool
     * @param ResourceModelRule $rule
     * @param Segment $segmentCollectionFactory
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Visibility $visibility
     * @param Customer $customer
     * @param Session $session
     * @param CustomerSegmentHelper $customerSegmentData
     * @param TargetRuleHelper $targetRuleData
     * @param Registry $coreRegistry
     * @param Stock|null $stockHelper
     * @param string|null $connectionName
     * @param HttpContext|null $httpContext
     * @param StockConfigurationInterface|null $stockConfiguration
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        IndexPool $indexPool,
        ResourceModelRule $rule,
        Segment $segmentCollectionFactory,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        Visibility $visibility,
        Customer $customer,
        Session $session,
        CustomerSegmentHelper $customerSegmentData,
        TargetRuleHelper $targetRuleData,
        Registry $coreRegistry,
        Stock $stockHelper = null,
        string $connectionName = null,
        HttpContext $httpContext = null,
        StockConfigurationInterface $stockConfiguration = null
    ) {
        $this->_indexPool = $indexPool;
        $this->_rule = $rule;
        $this->_segmentCollectionFactory = $segmentCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_visibility = $visibility;
        $this->_customer = $customer;
        $this->_session = $session;
        $this->_coreRegistry = $coreRegistry;
        $this->_customerSegmentData = $customerSegmentData;
        $this->_targetRuleData = $targetRuleData;
        $this->stockHelper = $stockHelper
            ?: ObjectManager::getInstance()->get(Stock::class);
        $this->httpContext = $httpContext
            ?: ObjectManager::getInstance()->get(HttpContext::class);
        $this->stockConfiguration = $stockConfiguration
            ?: ObjectManager::getInstance()->get(StockConfigurationInterface::class);

        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize connection and define main table
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_targetrule_index', 'entity_id');
    }

    /**
     * Retrieve constant value overfill limit for product ids index
     *
     * @return int
     */
    public function getOverfillLimit()
    {
        return 20;
    }

    /**
     * Retrieve array of defined product list type id
     *
     * @return int[]
     */
    public function getTypeIds()
    {
        return [
            Rule::RELATED_PRODUCTS,
            Rule::UP_SELLS,
            Rule::CROSS_SELLS
        ];
    }

    /**
     * Retrieve product Ids
     *
     * @param TargetRuleIndex $object
     * @param int|null $customerId
     * @param int|null $websiteId
     * @return array
     */
    public function getProductIds($object, $customerId = null, $websiteId = null)
    {
        $segmentsIds = array_merge(
            [$this->defaultSegmentId],
            $this->_getSegmentsIdsFromCurrentCustomer($customerId, $websiteId)
        );

        $productIdsByPriority = [];
        foreach ($segmentsIds as $segmentId) {
            $matchedProductIds = $this->_indexPool->get($object->getType())
                ->loadProductIdsBySegmentId($object, $segmentId);

            if (empty($matchedProductIds)) {
                $matchedProductIds = $this->_matchProductIdsBySegmentId($object, $segmentId);
                $this->_indexPool->get($object->getType())
                    ->saveResultForCustomerSegments(
                        $object,
                        $segmentId,
                        $matchedProductIds
                    );
            }
            foreach ($matchedProductIds as $productId => $priority) {
                if (!isset($productIdsByPriority[$productId]) || $productIdsByPriority[$productId] < $priority) {
                    $productIdsByPriority[$productId] = $priority;
                }
            }
        }

        return array_diff_key($productIdsByPriority, array_flip($object->getExcludeProductIds()));
    }

    /**
     * Match, save and return applicable product ids by segmentId object
     *
     * @param TargetRuleIndex $object
     * @param string $segmentId
     * @return array
     */
    protected function _matchProductIdsBySegmentId($object, $segmentId)
    {
        $limit = $object->getLimit() + $this->getOverfillLimit();
        $productIds = [];
        $ruleCollection = $object->getRuleCollection();
        if ($this->_customerSegmentData->isEnabled()) {
            $ruleCollection->addSegmentFilter($segmentId);
        }
        foreach ($ruleCollection as $rule) {
            /* @var Rule $rule */
            if (count($productIds) >= $limit) {
                break;
            }
            if (!$rule->checkDateForStore($object->getStoreId())) {
                continue;
            }
            $excludeProductIds = array_keys($productIds);
            $excludeProductIds[] = $object->getProduct()->getEntityId();
            $resultIds = $this->_getProductIdsByRule($rule, $object, $rule->getPositionsLimit(), $excludeProductIds);
            $productIds += array_fill_keys($resultIds, $rule->getSortOrder());
        }
        return $productIds;
    }

    /**
     * Retrieve found product ids by Rule action conditions
     *
     * If rule has cached select - get it
     *
     * @param Rule $rule
     * @param TargetRuleIndex $object
     * @param int $limit
     * @param array $excludeProductIds
     * @return array
     */
    protected function _getProductIdsByRule($rule, $object, $limit, $excludeProductIds = [])
    {
        /* @var Collection $collection */
        $collection = $this->_productCollectionFactory->create()->setStoreId(
            $object->getStoreId()
        )->addPriceData(
            $object->getCustomerGroupId()
        )->setVisibility(
            $this->_visibility->getVisibleInCatalogIds()
        );

        $actionSelect = $rule->getActionSelect();
        $actionBind = $rule->getActionSelectBind();

        if ($actionSelect === null) {
            $actionBind = [];
            $actionSelect = $rule->getActions()->getConditionForCollection($collection, $object, $actionBind);
            $rule->setActionSelect((string)$actionSelect)->setActionSelectBind($actionBind)->save();
        }

        if ($actionSelect) {
            $collection->getSelect()->where($actionSelect);
        }
        if ($excludeProductIds) {
            $collection->addFieldToFilter('entity_id', ['nin' => $excludeProductIds]);
        }

        if (!$this->stockConfiguration->isShowOutOfStock()) {
            $this->stockHelper->addInStockFilterToCollection($collection);
        }

        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns('entity_id', 'e');
        $select->limit($limit);

        $bind = $this->_prepareRuleActionSelectBind($object, $actionBind, $rule);
        $result = $this->getConnection()->fetchCol($select, $bind);

        return $result;
    }

    /**
     * Prepare bind array for product select
     *
     * @param TargetRuleIndex $object
     * @param array $actionBind
     * @param Rule $rule
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws LocalizedException
     */
    protected function _prepareRuleActionSelectBind(TargetRuleIndex $object, array $actionBind, Rule $rule): array
    {
        $bind = [];
        if (!is_array($actionBind)) {
            $actionBind = [];
        }

        foreach ($actionBind as $bindData) {
            if (!is_array($bindData) || !array_key_exists('bind', $bindData) || !array_key_exists('field', $bindData)
            ) {
                continue;
            }
            $k = $bindData['bind'];
            $v = null;
            if ($bindData['field'] === 'category_ids') {
                $v = $this->extractCategoryIdsFromRule($rule);
            }
            if (!$v) {
                $v = $object->getProduct()->getData($bindData['field']);
            }

            if (!empty($bindData['callback'])) {
                $callbacks = $bindData['callback'];
                if (!is_array($callbacks)) {
                    $callbacks = [$callbacks];
                }
                foreach ($callbacks as $callback) {
                    if (is_array($callback)) {
                        $v = $this->{$callback[0]}($v, $callback[1]);
                    } else {
                        $v = $this->{$callback}($v);
                    }
                }
            }

            if (is_array($v)) {
                $v = join(',', $v);
            }

            $bind[$k] = $v;
        }

        return $bind;
    }

    /**
     * Extracts Category ids from Rule
     *
     * @param Rule $rule
     * @return string
     */
    private function extractCategoryIdsFromRule(Rule $rule): string
    {
        $value = '';
        foreach ($rule->getConditions()->getConditions() as $condition) {
            if ($condition->getAttribute() === 'category_ids') {
                $value = $condition->getValue();
                break;
            }
        }

        return $value;
    }

    /**
     * Retrieve new SELECT instance (used Read Adapter)
     *
     * @return Select
     */
    public function select()
    {
        return $this->getConnection()->select();
    }

    /**
     * Retrieve SQL condition fragment by field, operator and value
     *
     * @param string $field
     * @param string $operator
     * @param int|string|array $value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOperatorCondition($field, $operator, $value)
    {
        switch ($operator) {
            case '!=':
            case '>=':
            case '<=':
            case '>':
            case '<':
                $selectOperator = sprintf('%s?', $operator);
                break;
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    $selectOperator = ' IN (?)';
                } else {
                    $selectOperator = ' LIKE ?';
                    $value = '%' . $value . '%';
                }
                if (substr($operator, 0, 1) == '!') {
                    $selectOperator = ' NOT' . $selectOperator;
                }
                break;

            case '()':
                $selectOperator = ' IN(?)';
                break;

            case '!()':
                $selectOperator = ' NOT IN(?)';
                break;

            default:
                $selectOperator = '=?';
                break;
        }
        $field = $this->getConnection()->quoteIdentifier($field);
        return $this->getConnection()->quoteInto("{$field}{$selectOperator}", $value);
    }

    /**
     * Retrieve SQL condition fragment by field, operator and binded value
     *
     * Modify bind array as well
     *
     * @param string $field
     * @param mixed $attribute
     * @param string $operator
     * @param array $bind
     * @param array $callback
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getOperatorBindCondition($field, $attribute, $operator, &$bind, $callback = [])
    {
        $field = $this->getConnection()->quoteIdentifier($field);
        $bindName = ':targetrule_bind_' . $this->_bindIncrement++;
        switch ($operator) {
            case '!=':
            case '>=':
            case '<=':
            case '>':
            case '<':
                $condition = sprintf('%s%s%s', $field, $operator, $bindName);
                break;
            case '{}':
                $condition = sprintf('%s LIKE %s', $field, $bindName);
                $callback[] = 'bindLikeValue';
                break;

            case '!{}':
                $condition = sprintf('%s NOT LIKE %s', $field, $bindName);
                $callback[] = 'bindLikeValue';
                break;

            case '()':
                $condition = $this->getConnection()->prepareSqlCondition(
                    $bindName,
                    ['finset' => new \Zend_Db_Expr($field)]
                );
                break;

            case '!()':
                $condition = $this->getConnection()->prepareSqlCondition(
                    $bindName,
                    ['finset' => new \Zend_Db_Expr($field)]
                );
                $condition = sprintf('NOT (%s)', $condition);
                break;

            default:
                $condition = sprintf('%s=%s', $field, $bindName);
                break;
        }

        $bind[] = ['bind' => $bindName, 'field' => $attribute, 'callback' => $callback];

        return $condition;
    }

    /**
     * Prepare bind value for LIKE condition
     *
     * @param string $value
     * @return string
     */
    public function bindLikeValue($value)
    {
        return '%' . $value . '%';
    }

    /**
     * Prepare bind array of ids from string or array
     *
     * @param string|int|array $value
     * @return array
     */
    public function bindArrayOfIds($value)
    {
        if (!is_array($value)) {
            $value = explode(',', $value);
        }

        $value = array_map('trim', $value);
        $value = array_filter($value, 'is_numeric');

        return $value;
    }

    /**
     * Prepare bind value (percent of value)
     *
     * @param float $value
     * @param int $percent
     * @return float
     */
    public function bindPercentOf($value, $percent)
    {
        return round($value * ($percent / 100), 4);
    }

    /**
     * Remove index data from index tables
     *
     * @param int|null $typeId
     * @param Store|int|array|null $store
     * @return $this
     */
    public function cleanIndex($typeId = null, $store = null)
    {
        $connection = $this->getConnection();

        if ($store instanceof Store) {
            $store = $store->getId();
        }

        if ($typeId === null) {
            foreach ($this->getTypeIds() as $typeId) {
                $this->_indexPool->get($typeId)->cleanIndex($store);
            }

            $where = $store === null ? '' : ['store_id IN(?)' => $store];
            $connection->delete($this->getMainTable(), $where);
        } else {
            $where = ['type_id=?' => $typeId];
            if ($store !== null) {
                $where['store_id IN(?)'] = $store;
            }
            $connection->delete($this->getMainTable(), $where);
            $this->_indexPool->get($typeId)->cleanIndex($store);
        }

        return $this;
    }

    /**
     * Remove products from index tables
     *
     * @param int|null $productId
     * @return $this
     */
    public function deleteProductFromIndex($productId = null)
    {
        foreach ($this->getTypeIds() as $typeId) {
            $this->_indexPool->get($typeId)->deleteProductFromIndex($productId);
        }
        return $this;
    }

    /**
     * Remove target rule matched product index data by product id or/and rule id
     *
     * @param array|int|null $productId
     * @param array|int|string $ruleIds
     * @return $this
     */
    public function removeProductIndex($productId = null, $ruleIds = [])
    {
        $this->_rule->unbindRuleFromEntity($ruleIds, $productId, 'product');
        return $this;
    }

    /**
     * Bind target rule to specified product
     *
     * @param Rule $object
     * @return $this
     */
    public function saveProductIndex($object)
    {
        $this->_rule->bindRuleToEntity($object->getId(), $object->getMatchingProductIds(), 'product');
        return $this;
    }

    /**
     * Adds order by random to select object
     *
     * @param Select $select
     * @param string|null $field
     * @return $this
     */
    public function orderRand(Select $select, $field = null)
    {
        $this->getConnection()->orderRand($select, $field);
        return $this;
    }

    /**
     * Get SegmentsIds From Current Customer and Website using globals
     *
     * @param int|null $customerId
     * @param int|null $websiteId
     * @return array
     */
    protected function _getSegmentsIdsFromCurrentCustomer(int $customerId = null, int $websiteId = null)
    {
        $segmentIds = [];
        if ($this->_customerSegmentData->isEnabled()) {
            if ($customerId === null) {
                $customer = $this->_coreRegistry->registry('segment_customer');
                if (!$customer) {
                    $customer = $this->_session->getCustomer();
                }
                $customerId = $customer->getId();
            }

            if ($websiteId === null) {
                $websiteId = $this->_storeManager->getWebsite()->getId();
            }

            $segmentIds = empty($customerId)
                ? (array)$this->httpContext->getValue(CustomerSegmentHelper::CONTEXT_SEGMENT)
                : $this->_customer->getCustomerSegmentIdsForWebsite((int)$customerId, (int)$websiteId);

            if (count($segmentIds)) {
                $segmentIds = $this->_segmentCollectionFactory->getActiveSegmentsByIds($segmentIds);
            }
        }

        return $segmentIds;
    }
}
