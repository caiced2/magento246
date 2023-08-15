<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\Rule\Condition\Wishlist;

/**
 * Product attribute value condition
 */
class Attributes extends \Magento\Rule\Model\Condition\Product\AbstractProduct
{
    /**
     * Config
     *
     * @var \Magento\Eav\Model\Config
     */
    protected $_config;

    /**
     * Rule Resource
     *
     * @var \Magento\Reminder\Model\ResourceModel\Rule
     */
    protected $_ruleResource;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Reminder\Model\ResourceModel\Rule $ruleResource
     * @param array $data
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
        \Magento\Reminder\Model\ResourceModel\Rule $ruleResource,
        array $data = []
    ) {
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
        $this->setType(\Magento\Reminder\Model\Rule\Condition\Wishlist\Attributes::class);
        $this->setValue(null);
        $this->_config = $config;
        $this->_ruleResource = $ruleResource;
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
            $this->_defaultOperatorInputByType['category'] = ['{}', '!{}'];
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
        return __('Product %1', strtolower(parent::asHtml()));
    }

    /**
     * Get product attribute object
     *
     * @return \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    public function getAttributeObject()
    {
        return $this->_config->getAttribute('catalog_product', $this->getAttribute());
    }

    /**
     * Get resource
     *
     * @return \Magento\Reminder\Model\ResourceModel\Rule
     */
    public function getResource()
    {
        return $this->_ruleResource;
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
     * @param int|\Zend_Db_Expr $website
     * @return string
     */
    public function getSubfilterSql($fieldName, $requireValid, $website)
    {
        $attribute = $this->getAttributeObject();
        $table = $attribute->getBackendTable();

        $resource = $this->getResource();
        $select = $resource->getConnection()->select();
        $select->from(['main' => $table], ['entity_id']);

        if ($attribute->getAttributeCode() == 'category_ids') {
            $condition = $resource->createConditionSql(
                'cat.category_id',
                $this->getOperatorForValidate(),
                $this->getValueParsed()
            );
            $categorySelect = $resource->getConnection()->select();
            $categorySelect->from(
                ['cat' => $resource->getTable('catalog_category_product')],
                'product_id'
            )->where(
                $condition
            );
            $condition = 'main.entity_id IN (' . $categorySelect . ')';
        } elseif ($attribute->isStatic()) {
            $attrCol = $select->getConnection()->quoteColumnAs('main.' . $attribute->getAttributeCode(), null);
            $condition = $this->getResource()->createConditionSql($attrCol, $this->getOperator(), $this->getValue());
        } else {
            $select->where('main.attribute_id = ?', $attribute->getId());
            $select->join(
                ['store' => $this->getResource()->getTable('store')],
                'main.store_id=store.store_id',
                []
            )->where(
                'store.website_id IN(?)',
                [0, $website]
            );
            $condition = $this->getResource()->createConditionSql(
                'main.value',
                $this->getOperator(),
                $this->getValue()
            );
        }
        $select->where($condition);
        $inOperator = $requireValid ? 'IN' : 'NOT IN';
        return sprintf("%s %s (%s)", $fieldName, $inOperator, $select);
    }
}
