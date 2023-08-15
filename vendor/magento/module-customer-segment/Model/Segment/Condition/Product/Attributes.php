<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Framework\EntityManager\MetadataPool;
use Zend_Db_Expr;

/**
 * Product attributes condition data model.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attributes extends \Magento\Rule\Model\Condition\Product\AbstractProduct
{
    /**
     * Used for rule property field
     *
     * @var string
     */
    protected $_isUsedForRuleProperty = 'is_used_for_promo_rules';

    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $_resourceSegment;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote
     */
    protected $quoteResource;

    /**
     * @var MetadataPool
     */
    private $entityManagerMetadataPool;

    /**
     * @var string
     */
    private $productLinkField;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Quote\Model\ResourceModel\Quote $quoteResource
     * @param array $data
     * @param MetadataPool $metadataPool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\Backend\Helper\Data $backendData,
        \Magento\Eav\Model\Config $config,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Quote\Model\ResourceModel\Quote $quoteResource,
        array $data = [],
        MetadataPool $metadataPool = null
    ) {
        $this->_resourceSegment = $resourceSegment;
        $this->quoteResource = $quoteResource;
        parent::__construct(
            $context,
            $backendData,
            $config,
            $productFactory,
            $productRepository,
            $productResource,
            $attrSetCollection,
            $localeFormat,
            $data
        );
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Product\Attributes::class);
        $this->setValue(null);
        $this->entityManagerMetadataPool = $metadataPool
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(MetadataPool::class);
    }

    /**
     * Customize default operator input by type mapper for some types
     *
     * @return array
     */
    public function getDefaultOperatorInputByType()
    {
        if (null === $this->_defaultOperatorInputByType) {
            parent::getDefaultOperatorInputByType();
            $this->_defaultOperatorInputByType['numeric'] = ['==', '!=', '>=', '>', '<=', '<'];
            $this->_defaultOperatorInputByType['string'] = ['==', '!=', '{}', '!{}'];
        }
        return $this->_defaultOperatorInputByType;
    }

    /**
     * Get input type for attribute operators.
     *
     * @return string
     */
    public function getInputType()
    {
        if (!is_object($this->getAttributeObject())) {
            return 'string';
        }
        if ($this->getAttributeObject()->getAttributeCode() == 'category_ids') {
            return 'category';
        }
        $input = $this->getAttributeObject()->getFrontendInput();
        switch ($input) {
            case 'select':
            case 'multiselect':
            case 'date':
                return $input;
            default:
                return 'string';
        }
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $attributes = $this->loadAttributeOptions()->getAttributeOption();
        $conditions = [];
        foreach ($attributes as $code => $label) {
            $conditions[] = ['value' => $this->getType() . '|' . $code, 'label' => $label];
        }

        return ['value' => $conditions, 'label' => __('Product Attributes')];
    }

    /**
     * Get HTML of condition string
     *
     * @return \Magento\Framework\Phrase
     */
    public function asHtml()
    {
        return __('Product %1', parent::asHtml());
    }

    /**
     * Get product attribute object
     *
     * @return Attribute
     */
    public function getAttributeObject()
    {
        return $this->_config->getAttribute('catalog_product', $this->getAttribute());
    }

    /**
     * Get resource
     *
     * @return \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    public function getResource()
    {
        return $this->_resourceSegment;
    }

    /**
     * Get used subfilter type
     *
     * @return string
     */
    public function getSubfilterType()
    {
        return 'product';
    }

    /**
     * Apply product attribute subfilter to parent/base condition query
     *
     * @param string $fieldName base query field name
     * @param bool $requireValid strict validation flag
     * @param int|Zend_Db_Expr $website
     * @return string
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $select = $this->buildAttributeSelect($website);
        $inOperator = $requireValid ? 'IN' : 'NOT IN';
        if ($this->getCombineProductCondition()) {
            // when used as a child of History or List condition - "IN" always set to "IN"
            $inOperator = 'IN';
        }

        $productIds = $this->getData('product_ids');

        if ($productIds) {
            $select->where("main.entity_id IN (?)", $productIds);
        }

        if ($this->getUseSelect() !== false) {
            $subQuery = sprintf("%s %s (%s)", $fieldName, $inOperator, $select);
        } else {
            $result = $this->getResource()->getConnection()->fetchCol($select);
            if ($result) {
                $subQuery = sprintf("%s %s (%s)", $fieldName, $inOperator, implode(',', $result));
            } else {
                $subQuery = $inOperator === 'IN' ? '1=0' : '1=1';
            }
        }

        return $subQuery;
    }

    /**
     * Check if product provided in params is matched by current attribute condition.
     *
     * @param int $customer
     * @param int $website
     * @param array $params
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isSatisfiedBy($customer, $website, $params)
    {
        $productId = $params['quote_item']['product_id'];
        $select = $this->buildAttributeSelect($website);
        $select->where("main.entity_id = ?", $productId);
        $result = $this->getResource()->getConnection()->fetchCol($select);

        return !empty($result);
    }

    /**
     * Get list of quote Ids, matching to current attribute condition.
     *
     * @param int $websiteId
     * @return int[]
     */
    public function getSatisfiedIds($websiteId)
    {
        $select = $this->buildAttributeSelect($websiteId);
        $result = $this->getResource()->getConnection()->fetchCol($select);
        $quoteIds = [];
        if (!empty($result)) {
            $quoteIds = $this->executePrepareConditionSql($websiteId, $result);
        }
        return $quoteIds;
    }

    /**
     * Executes prepared condition sql.
     *
     * @param int $websiteId
     * @param array $productIds
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function executePrepareConditionSql($websiteId, $productIds)
    {
        $select = $this->quoteResource->getConnection()->select();
        $select->from(
            ['item' => $this->getResource()->getTable('quote_item')],
            ['quote_id']
        );
        $conditions = "item.quote_id = list.entity_id";
        $select->joinInner(
            ['list' => $this->getResource()->getTable('quote')],
            $conditions,
            []
        );
        $select->where('list.is_active = ?', new \Zend_Db_Expr(1));
        $select->where('item.product_id IN(?)', $productIds);
        $select->where(
            'list.store_id IN(?)',
            $this->getStoreByWebsite($websiteId)
        );
        $result = $this->quoteResource->getConnection()->fetchCol($select);

        return $result;
    }

    /**
     * Returns store data.
     *
     * @param int $websiteId
     * @return array
     */
    protected function getStoreByWebsite($websiteId)
    {
        $storeTable = $this->getResource()->getTable('store');
        $storeSelect = $this->getResource()->createSelect()->from($storeTable, ['store_id'])
            ->where('website_id=?', $websiteId);
        $data = $this->getResource()->getConnection()->fetchCol($storeSelect);
        return $data;
    }

    /**
     * Get product entity link field name.
     *
     * @return string
     */
    private function getProductLinkField()
    {
        if (!$this->productLinkField) {
            $productMetadata = $this->entityManagerMetadataPool->getMetadata(ProductInterface::class);
            $this->productLinkField = $productMetadata->getLinkField();
        }
        return $this->productLinkField;
    }

    /**
     * Create select object for current attribute.
     *
     * This select will return IDs of products matching current attribute condition.
     *
     * @param int|Zend_Db_Expr $website
     * @return \Magento\Framework\DB\Select
     */
    private function buildAttributeSelect($website)
    {
        $resource = $this->getResource();
        $select = $resource->createSelect();
        $linkField = $this->getProductLinkField();
        $attribute = $this->getAttributeObject();
        $attributeTable = $attribute->getBackendTable();
        $productTable = $resource->getTable('catalog_product_entity');
        $attributeTableAlias = $this->getAttributeTableAlias();

        $select->from(['main' => $productTable], ['entity_id']);
        if ($productTable !== $attributeTable) {
            // If attribute backend table is different from 'catalog_product_entity', then 'catalog_product_entity'
            // should be joined to be able to filter by 'entity_id' with enabled staging
            $select->join(
                [$this->getAttributeTableAlias() => $attributeTable],
                "{$attributeTableAlias}.{$linkField}=main.{$linkField}",
                []
            );
        }

        if ($attribute->getAttributeCode() == 'category_ids') {
            $condition = $this->buildCategoryIdsCondition();
        } elseif ($attribute->isStatic()) {
            $condition = $this->buildStaticAttributeCondition();
        } else {
            $select->where("{$attributeTableAlias}.attribute_id = ?", $attribute->getId())
                ->join(['store' => $resource->getTable('store')], "{$attributeTableAlias}.store_id=store.store_id", [])
                ->where('store.website_id IN (?)', [0, $website]);
            $condition = $this->buildNonStaticAttributeCondition();
        }
        $select->where($condition);

        return $select;
    }

    /**
     * Build SQL condition for 'category_ids' attribute.
     *
     * @return string
     */
    private function buildCategoryIdsCondition()
    {
        $resource = $this->getResource();
        $categorySelectCondition = $resource->createConditionSql(
            'cat.category_id',
            $this->getOperator(),
            $this->getValueParsed()
        );
        $categorySelect = $resource->createSelect();
        $categorySelect->from(['cat' => $resource->getTable('catalog_category_product')], 'product_id')
            ->where($categorySelectCondition);
        $entityIds = implode(',', $this->getResource()->getConnection()->fetchCol($categorySelect));
        $result = empty($entityIds) ? 'FALSE' : 'main.entity_id IN (' . $entityIds . ')';

        return $result;
    }

    /**
     * Build SQL condition for non-static attribute.
     *
     * @return string
     */
    private function buildNonStaticAttributeCondition()
    {
        return $this->getResource()->createConditionSql(
            "{$this->getAttributeTableAlias()}.value",
            $this->getOperator(),
            $this->getValue()
        );
    }

    /**
     * Build SQL condition for static attribute.
     *
     * @return string
     */
    private function buildStaticAttributeCondition()
    {
        $attribute = $this->getAttributeObject();
        return $this->getResource()->createConditionSql(
            "{$this->getAttributeTableAlias()}.{$attribute->getAttributeCode()}",
            $this->getOperator(),
            $this->getValue()
        );
    }

    /**
     * Get alias for the table holding attribute data.
     *
     * @return string
     */
    private function getAttributeTableAlias()
    {
        $attributeTable = $this->getAttributeObject()->getBackendTable();
        $productTable = $this->getResource()->getTable('catalog_product_entity');
        return ($productTable === $attributeTable) ? 'main' : 'eav_attribute';
    }
}
