<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model;

use InvalidArgumentException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\VisualMerchandiser\Model\Position\Cache;
use Magento\VisualMerchandiser\Model\Rules\Factory;
use Magento\VisualMerchandiser\Model\Rules\Rule\Collection\Fetcher;
use Magento\VisualMerchandiser\Model\ResourceModel\Rules as ResourceModelRules;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\App\ObjectManager;

/**
 * @method bool getIsActive()
 * @method string getConditionsSerialized()
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Rules extends AbstractModel
{
    /**
     * Additional attributes available to smart category rules
     */
    public const XML_PATH_AVAILABLE_ATTRIBUTES = 'visualmerchandiser/options/smart_attributes';

    /**
     * @var array
     */
    protected $notices = [];

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Attribute
     */
    protected $attribute;

    /**
     * @var Factory
     */
    protected $ruleFactory;

    /**
     * @var Fetcher
     */
    protected $fetcher;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $messageManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Attribute $attribute
     * @param Factory $ruleFactory
     * @param Fetcher $fetcher
     * @param array $data
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param Cache|null $cache
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        ScopeConfigInterface $scopeConfig,
        Attribute $attribute,
        Factory $ruleFactory,
        Fetcher $fetcher,
        array $data = [],
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        Cache $cache = null
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
        $this->_scopeConfig = $scopeConfig;
        $this->attribute = $attribute;
        $this->ruleFactory = $ruleFactory;
        $this->fetcher = $fetcher;
        $this->cache = $cache ?: ObjectManager::getInstance()->get(Cache::class);
    }

    /**
     * Init resource model and id field
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(ResourceModelRules::class);
        $this->setIdFieldName('rule_id');
    }

    /**
     * Before save
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->validateData();
        return parent::beforeSave();
    }

    /**
     * Validate the obvious
     *
     * @return void
     */
    protected function validateData()
    {
        if (!$this->getData('is_active')) {
            return;
        }
        try {
            $conditionsJson = $this->getData('conditions_serialized');
            if ($conditionsJson) {
                $this->decode((string)$conditionsJson);
            }
        } catch (InvalidArgumentException $e) {
            $this->_messageManager->addException($e, __("Category rules validation failed"));
            $this->setData('conditions_serialized', null);
        }
    }

    /**
     * Get mode
     *
     * @return int
     */
    public function getMode()
    {
        return (int) $this->getData('mode');
    }

    /**
     * Get the attributes usable with VisualMerchandiser rules
     *
     * @return array
     */
    public function getAvailableAttributes()
    {
        $attributesString = $this->_scopeConfig->getValue(self::XML_PATH_AVAILABLE_ATTRIBUTES);
        $attributes = is_string($attributesString) ? explode(',', $attributesString) : [];
        $attributes = array_map('trim', $attributes);

        $result = [];
        foreach ($attributes as $attributeCode) {
            $attribute = $this->attribute->loadByCode(
                Product::ENTITY,
                $attributeCode
            );
            if (!$attribute->getId()) {
                continue;
            }
            $result[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }

        $this->addStaticOptions($result);

        asort($result);
        return $result;
    }

    /**
     * Add static options
     *
     * @param array $options
     * @return void
     */
    protected function addStaticOptions(array &$options)
    {
        $options['category_id'] = __('Clone category ID(s)');
        $options['created_at'] = __('Date Created (days ago)');
        $options['updated_at'] = __('Date Modified (days ago)');
    }

    /**
     * Get logic variants
     *
     * @return array
     */
    public static function getLogicVariants() // phpcs:ignore Magento2.Functions.StaticFunction
    {
        return [
            Select::SQL_OR,
            Select::SQL_AND
        ];
    }

    /**
     * Get product collection
     *
     * @return Collection
     *
     * @deprecated 100.3.1
     * @see getProductCollectionByWebsite
     */
    protected function getProductCollection()
    {
        return $this->_productCollectionFactory->create()->setStoreId(
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * Load by category
     *
     * @param Category $category
     * @return Rules
     */
    public function loadByCategory(Category $category)
    {
        return $this->load($category->getId(), 'category_id');
    }

    /**
     * Get conditions
     *
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function getConditions()
    {
        if (!$this->getId()) {
            return null;
        }

        $conditionsSerialized = $this->getData('conditions_serialized');
        if (!$conditionsSerialized) {
            return null;
        }

        return $this->decode((string)$conditionsSerialized);
    }

    /**
     * Apply all rules
     *
     * @param Category $category
     * @param Collection $collection
     * @return void
     */
    public function applyAllRules(Category $category, Collection $collection)
    {
        $rules = $this->loadByCategory($category);

        if (!$rules || !$rules->getIsActive()) {
            return;
        }

        try {
            $conditions = $rules->getConditions();
        } catch (InvalidArgumentException $e) {
            $this->_messageManager->addException($e, __("Error in reading category rules"));
            return;
        }

        if (!is_array($conditions) || count($conditions) == 0) {
            $this->_messageManager->addError(__("There was no category rules to apply"));
            return;
        }

        $this->applyConditions($category, $collection, $conditions);

        if (!empty($this->notices)) {
            foreach ($this->notices as $notice) {
                $this->_messageManager->addNotice($notice);
            }
        }

        if ($this->_messageManager->hasMessages()) {
            return;
        }

        $this->_messageManager->addSuccess(__("Category rules applied"));
    }

    /**
     * Apply conditions
     *
     * @param Category $category
     * @param Collection $collection
     * @param array $conditions
     * @return void
     */
    public function applyConditions(Category $category, Collection $collection, array $conditions)
    {
        $ids = [];
        $logic = "";
        foreach ($conditions as $rule) {
            $websiteId = (int)$category->getStore()->getWebsiteId();
            $_collection = $this->getProductCollectionByWebsite($websiteId);

            $ruleType = $this->ruleFactory->create($rule);
            $ruleType->applyToCollection($_collection);

            $ids = ($logic == Select::SQL_AND)
                ? array_intersect($ids, $this->fetcher->fetchIds($_collection))
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                : array_merge($ids, $this->fetcher->fetchIds($_collection));

            $logic = strtoupper($rule['logic']);

            if ($ruleType->hasNotices()) {
                $this->notices = $this->notices + $ruleType->getNotices();
            }
        }

        $collection->addAttributeToFilter('entity_id', ['in' => $ids]);
        if (count($ids) > 0) {
            $collection->getSelect()->reset(Select::ORDER);
            $collection->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $ids) . ')'));
        }

        $positions = $this->getProductsPositions($collection, $ids);

        $category->setPostedProducts($positions);

        // Clear any data that collection cached so far
        if ($collection->isLoaded()) {
            $collection->clear();
        }
    }

    /**
     * Get products positions from cache or regenerate
     *
     * @param Collection $collection
     * @param array $ids
     * @return array
     */
    private function getProductsPositions(Collection $collection, array $ids): array
    {
        $positions = $this->cache->getPositions(Cache::POSITION_CACHE_KEY);
        if (!$positions || count($ids) != count($positions) || array_diff($ids, array_keys($positions))) {
            $positions = [];
            foreach ($collection as $key => $item) {
                /* @var $item ProductInterface */
                $positions[$item->getId()] = $key;
            }
        }

        return $positions;
    }

    /**
     * Get product collection filtered by website
     *
     * @param int $websiteId
     * @return Collection
     */
    private function getProductCollectionByWebsite(int $websiteId)
    {
        $productCollection = $this->_productCollectionFactory->create()->setStoreId(
            $this->_storeManager->getStore()->getId()
        );
        if ($websiteId) {
            $productCollection->addWebsiteFilter($websiteId);
        }
        return $productCollection;
    }

    /**
     * Decode a value
     *
     * @param string $encodedValue
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    private function decode(string $encodedValue)
    {
        $decoded = json_decode($encodedValue, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
        }

        return $decoded;
    }
}
