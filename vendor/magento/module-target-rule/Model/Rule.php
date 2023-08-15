<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model;

use Laminas\Validator\Regex;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

/**
 * TargetRule Rule Model
 *
 * @method string getName()
 * @method \Magento\TargetRule\Model\Rule setName(string $value)
 * @method string getFromDate()
 * @method \Magento\TargetRule\Model\Rule setFromDate(string $value)
 * @method string getToDate()
 * @method \Magento\TargetRule\Model\Rule setToDate(string $value)
 * @method int getIsActive()
 * @method \Magento\TargetRule\Model\Rule setIsActive(int $value)
 * @method string getConditionsSerialized()
 * @method \Magento\TargetRule\Model\Rule setConditionsSerialized(string $value)
 * @method string getActionsSerialized()
 * @method \Magento\TargetRule\Model\Rule setActionsSerialized(string $value)
 * @method \Magento\TargetRule\Model\Rule setPositionsLimit(int $value)
 * @method int getApplyTo()
 * @method \Magento\TargetRule\Model\Rule setApplyTo(int $value)
 * @method int getSortOrder()
 * @method \Magento\TargetRule\Model\Rule setSortOrder(int $value)
 * @method int getUseCustomerSegment()
 * @method \Magento\TargetRule\Model\Rule setUseCustomerSegment(int $value)
 * @method string getActionSelect()
 * @method \Magento\TargetRule\Model\Rule setActionSelect(string $value)
 * @method array getCustomerSegmentIds()
 * @method \Magento\TargetRule\Model\Rule setCustomerSegmentIds(array $ids)
 * @method \Magento\TargetRule\Model\Rule\Condition\Combine getConditions()
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Rule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * Position behavior selectors
     */
    public const BOTH_SELECTED_AND_RULE_BASED = 0;

    public const SELECTED_ONLY = 1;

    public const RULE_BASED_ONLY = 2;

    /**
     * Product list types
     */
    public const RELATED_PRODUCTS = 1;

    public const UP_SELLS = 2;

    public const CROSS_SELLS = 3;

    /**
     * Shuffle mode by default
     */
    public const ROTATION_SHUFFLE = 0;

    public const ROTATION_NONE = 1;

    /**
     * Store default product positions limit
     */
    public const POSITIONS_DEFAULT_LIMIT = 20;

    /**
     * Store matched product Ids
     *
     * @var array
     */
    protected $_productIds;

    /**
     * Store flags per store is applicable rule by date
     *
     * @var array
     */
    protected $_checkDateForStore = [];

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\TargetRule\Model\Rule\Condition\CombineFactory
     */
    protected $_ruleFactory;

    /**
     * @var \Magento\TargetRule\Model\Actions\Condition\CombineFactory
     */
    protected $_actionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * Rule-Product indexer processor instance
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor
     */
    protected $_ruleProductIndexerProcessor;

    /**
     * @var \Magento\Rule\Model\Condition\Sql\Builder
     */
    protected $_sqlBuilder;

    /**
     * Rule constructor.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param Rule\Condition\CombineFactory $ruleFactory
     * @param Actions\Condition\CombineFactory $actionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param Indexer\TargetRule\Rule\Product\Processor $ruleProductIndexerProcessor
     * @param \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\TargetRule\Model\Rule\Condition\CombineFactory $ruleFactory,
        \Magento\TargetRule\Model\Actions\Condition\CombineFactory $actionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor $ruleProductIndexerProcessor,
        \Magento\Rule\Model\Condition\Sql\Builder $sqlBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_localeDate = $localeDate;
        $this->_productFactory = $productFactory;
        $this->_ruleFactory = $ruleFactory;
        $this->_actionFactory = $actionFactory;
        $this->_ruleProductIndexerProcessor = $ruleProductIndexerProcessor;
        $this->_sqlBuilder = $sqlBuilder;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Get products from provided collection
     *
     * @param ProductCollection $collection
     * @return \Generator|Product[]
     */
    private function getProducts(ProductCollection $collection): \Generator
    {
        $collection->setPageSize(1000);
        $pageCount = $collection->getLastPageNumber();
        $currentPage = 1;
        while ($currentPage <= $pageCount) {
            $collection->setCurPage($currentPage);
            foreach ($collection as $key => $product) {
                yield $key => $product;
            }
            $collection->clear();
            $currentPage++;
        }
    }

    /**
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\TargetRule\Model\ResourceModel\Rule::class);
    }

    /**
     * Reset action cached select if actions conditions has changed
     *
     * @return \Magento\TargetRule\Model\Rule
     */
    public function beforeSave()
    {
        parent::beforeSave();

        if ($this->dataHasChangedFor('actions_serialized')) {
            $this->setData('action_select', null);
            $this->setData('action_select_bind', null);
        }

        return $this;
    }

    /**
     * AfterSave callback
     *
     * @return $this
     */
    public function afterSave()
    {
        $data = [
            'is_active',
            'from_date',
            'to_date',
            'conditions',
            'apply_to',
            'actions',
            'customer_segment_ids',
        ];
        if ($this->isObjectNew() || $this->dataHasChangedForAny($data)) {
            $this->_ruleProductIndexerProcessor->reindexRow($this->getId());
        }
        return parent::afterSave();
    }

    /**
     * After delete callback
     *
     * @return $this
     */
    public function afterDeleteCommit()
    {
        $this->_ruleProductIndexerProcessor->reindexRow($this->getId());
        return parent::afterDeleteCommit();
    }

    /**
     * Check is data changed for any of provided fields
     *
     * @param string[] $fields
     * @return bool
     */
    public function dataHasChangedForAny(array $fields = [])
    {
        foreach ($fields as $field) {
            if ($this->dataHasChangedFor($field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Getter for rule combine conditions instance
     *
     * @return \Magento\TargetRule\Model\Rule\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->_ruleFactory->create();
    }

    /**
     * Getter for rule actions collection instance
     *
     * @return \Magento\TargetRule\Model\Actions\Condition\Combine
     */
    public function getActionsInstance()
    {
        return $this->_actionFactory->create();
    }

    /**
     * Get options for `Apply to` field
     *
     * @param bool $withEmpty
     * @return string[]|\Magento\Framework\Phrase[]
     */
    public function getAppliesToOptions($withEmpty = false)
    {
        $result = [];
        if ($withEmpty) {
            $result[''] = __('-- Please Select --');
        }
        $result[\Magento\TargetRule\Model\Rule::RELATED_PRODUCTS] = __('Related Products');
        $result[\Magento\TargetRule\Model\Rule::UP_SELLS] = __('Up-sells');
        $result[\Magento\TargetRule\Model\Rule::CROSS_SELLS] = __('Cross-sells');

        return $result;
    }

    /**
     * Prepare array of product ids which are matched by rule
     *
     * @return $this
     */
    public function prepareMatchingProducts()
    {
        $productCollection = $this->_productFactory->create()->getCollection();
        $this->setCollectedAttributes([]);
        $this->getConditions()->collectValidatedAttributes($productCollection);

        $where = $this->getConditions()->getConditionForCollection($productCollection);
        if ($where) {
            $productCollection->getSelect()->where($where);
        }

        $this->_productIds = [];
        foreach ($this->getProducts($productCollection) as $product) {
            if ($this->getConditions()->validate($product)) {
                $this->_productIds[] = $product->getId();
            }
        }
        return $this;
    }

    /**
     * Retrieve array of product Ids that are matched by rule
     *
     * @return int[]
     */
    public function getMatchingProductIds()
    {
        if (null === $this->_productIds) {
            $this->prepareMatchingProducts();
        }
        return $this->_productIds;
    }

    /**
     * Check if rule is applicable by date for specified store
     *
     * @param int $storeId
     * @return bool
     */
    public function checkDateForStore($storeId)
    {
        if (!isset($this->_checkDateForStore[$storeId])) {
            $this->_checkDateForStore[$storeId] = $this->_localeDate->isScopeDateInInterval(
                null,
                $this->getFromDate(),
                $this->getToDate()
            );
        }
        return $this->_checkDateForStore[$storeId];
    }

    /**
     * Get product positions for current rule
     *
     * @return int If positions limit is not set, then default limit will be returned
     */
    public function getPositionsLimit()
    {
        $limit = $this->getData('positions_limit');
        if (!$limit) {
            $limit = 20;
        }

        return $limit;
    }

    /**
     * Retrieve Action select bind array
     *
     * @return string[]
     */
    public function getActionSelectBind()
    {
        $bind = $this->getData('action_select_bind');
        if ($bind && is_string($bind)) {
            $bind = $this->serializer->unserialize($bind);
        }

        return $bind;
    }

    /**
     * Set action select bind array or serialized string
     *
     * @param string[]|string $bind
     * @return $this
     */
    public function setActionSelectBind($bind)
    {
        if (is_array($bind)) {
            $bind = $this->serializer->serialize($bind);
        }
        return $this->setData('action_select_bind', $bind);
    }

    /**
     * Validate rule data
     *
     * @param \Magento\Framework\DataObject $object
     * @return string[]|bool - Return true if validation passed successfully. Array with errors description otherwise
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateData(\Magento\Framework\DataObject $object)
    {
        $result = parent::validateData($object);

        if (!is_array($result)) {
            $result = [];
        }

        $validator = new Regex(['pattern' => '/^[a-z][a-z0-9_\/]{1,255}$/']);
        $actionArgsList = $object->getData('rule');
        if (is_array($actionArgsList) && isset($actionArgsList['actions'])) {
            foreach ($actionArgsList['actions'] as $actionArgsIndex => $actionArgs) {
                if (1 === $actionArgsIndex) {
                    continue;
                }
                if (!class_exists($actionArgs['type'])) {
                    throw new LocalizedException(
                        __("The attribute's model class name is invalid. Verify the name and try again.")
                    );
                }
                if (isset($actionArgs['attribute']) && !$validator->isValid($actionArgs['attribute'])) {
                    $result[] = __(
                        'This attribute code is invalid. Please use only letters (a-z), '
                        . 'numbers (0-9) or underscores (_), and be sure the code begins with a letter.'
                    );
                }
            }
        }

        return !empty($result) ? $result : true;
    }

    /**
     * Validate rule conditions to determine if rule can run
     *
     * @param int $entityId
     *
     * @return bool
     */
    public function validateByEntityId($entityId)
    {
        return $this->getConditions()->validateByEntityId($entityId);
    }
}
