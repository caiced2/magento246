<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model\Actions\Condition\Product;

use Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder;

/**
 * TargetRule Action Product Attributes Condition Model
 *
 * @author   Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attributes extends \Magento\TargetRule\Model\Rule\Condition\Product\Attributes
{
    /**
     * Value type values constants
     *
     */
    const VALUE_TYPE_CONSTANT = 'constant';

    const VALUE_TYPE_SAME_AS = 'same_as';

    const VALUE_TYPE_CHILD_OF = 'child_of';

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * @var \Magento\Rule\Block\Editable
     */
    protected $_editable;
    /**
     * @var SqlBuilder
     */
    private $sqlBuilder;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\Backend\Helper\Data $backendData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product $productResource
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $attrSetCollection
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Rule\Block\Editable $editable
     * @param \Magento\Catalog\Model\Product\Type $type
     * @param array $data
     * @param SqlBuilder $sqlBuilder
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
        \Magento\Rule\Block\Editable $editable,
        \Magento\Catalog\Model\Product\Type $type,
        array $data = [],
        SqlBuilder $sqlBuilder = null
    ) {
        $this->_editable = $editable;
        $this->_type = $type;
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
        $this->setType(\Magento\TargetRule\Model\Actions\Condition\Product\Attributes::class);
        $this->setValue(null);
        $this->setValueType(self::VALUE_TYPE_SAME_AS);
        $this->sqlBuilder = $sqlBuilder ?: \Magento\Framework\App\ObjectManager::getInstance()->get(SqlBuilder::class);
    }

    /**
     * Add special action product attributes
     *
     * @param array &$attributes
     * @return void
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['type_id'] = __('Type');
    }

    /**
     * Retrieve value by option
     *
     * Rewrite for Retrieve options by Product Type attribute
     *
     * @param mixed $option
     * @return string
     */
    public function getValueOption($option = null)
    {
        if (!$this->getData('value_option') && $this->getAttribute() == 'type_id') {
            $this->setData('value_option', $this->_type->getAllOption());
        }
        return parent::getValueOption($option);
    }

    /**
     * Retrieve select option values
     *
     * Rewrite Rewrite for Retrieve options by Product Type attribute
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        if (!$this->getData('value_select_options') && $this->getAttribute() == 'type_id') {
            $this->setData('value_select_options', $this->_type->getAllOption());
        }
        return parent::getValueSelectOptions();
    }

    /**
     * Retrieve input type
     *
     * Rewrite for define input type for Product Type attribute
     *
     * @return string
     */
    public function getInputType()
    {
        $attributeCode = $this->getAttribute();
        if ($attributeCode == 'type_id') {
            return 'select';
        }
        return parent::getInputType();
    }

    /**
     * Retrieve value element type
     *
     * Rewrite for define value element type for Product Type attribute
     *
     * @return string
     */
    public function getValueElementType()
    {
        $attributeCode = $this->getAttribute();
        if ($attributeCode == 'type_id') {
            return 'select';
        }
        return parent::getValueElementType();
    }

    /**
     * Retrieve model content as HTML
     *
     * Rewrite for add value type chooser
     *
     * @return \Magento\Framework\Phrase
     */
    public function asHtml()
    {
        return __(
            'Product %1%2%3%4%5%6%7',
            $this->getTypeElementHtml(),
            $this->getAttributeElementHtml(),
            $this->getOperatorElementHtml(),
            $this->getValueTypeElementHtml(),
            $this->getValueElementHtml(),
            $this->getRemoveLinkHtml(),
            $this->getChooserContainerHtml()
        );
    }

    /**
     * Returns options for value type select box
     *
     * @return array
     */
    public function getValueTypeOptions()
    {
        $options = [['value' => self::VALUE_TYPE_CONSTANT, 'label' => __('Constant Value')]];

        if ($this->getAttribute() == 'category_ids') {
            $options[] = [
                'value' => self::VALUE_TYPE_SAME_AS,
                'label' => __('the Same as Matched Product Categories'),
            ];
            $options[] = [
                'value' => self::VALUE_TYPE_CHILD_OF,
                'label' => __('the Child of the Matched Product Categories'),
            ];
        } else {
            $options[] = [
                'value' => self::VALUE_TYPE_SAME_AS,
                'label' => __('Matched Product %1', $this->getAttributeName()),
            ];
        }

        return $options;
    }

    /**
     * Retrieve Value Type display name
     *
     * @return string
     */
    public function getValueTypeName()
    {
        $options = $this->getValueTypeOptions();
        foreach ($options as $option) {
            if ($option['value'] == $this->getValueType()) {
                return $option['label'];
            }
        }
        return '...';
    }

    /**
     * Retrieve Value Type Select Element
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function getValueTypeElement()
    {
        $elementId = $this->getPrefix() . '__' . $this->getId() . '__value_type';
        $element = $this->getForm()->addField(
            $elementId,
            'select',
            [
                'name' => $this->elementName . '[' . $this->getPrefix() . '][' . $this->getId() . '][value_type]',
                'values' => $this->getValueTypeOptions(),
                'value' => $this->getValueType(),
                'value_name' => $this->getValueTypeName(),
                'class' => 'value-type-chooser'
            ]
        )->setRenderer(
            $this->_editable
        );
        return $element;
    }

    /**
     * Retrieve value type element HTML code
     *
     * @return string
     */
    public function getValueTypeElementHtml()
    {
        $element = $this->getValueTypeElement();
        return $element->getHtml();
    }

    /**
     * Load attribute property from array
     *
     * @param array $array
     * @return $this
     */
    public function loadArray($array)
    {
        parent::loadArray($array);

        if (isset($array['value_type'])) {
            $this->setValueType($array['value_type']);
        }
        return $this;
    }

    /**
     * Retrieve condition data as array
     *
     * @param array $arrAttributes
     * @return array
     */
    public function asArray(array $arrAttributes = [])
    {
        $array = parent::asArray($arrAttributes);
        $array['value_type'] = $this->getValueType();
        return $array;
    }

    /**
     * Retrieve condition data as string
     *
     * @param string $format
     * @return string
     */
    public function asString($format = '')
    {
        if (!$format) {
            $format = ' %s %s %s %s';
        }
        return sprintf(
            __('Target Product ')->render() . $format,
            $this->getAttributeName(),
            $this->getOperatorName(),
            $this->getValueTypeName(),
            $this->getValueName()
        );
    }
    /**
     * Retrieve SELECT WHERE condition for product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\TargetRule\Model\Index $object
     * @param array $bind
     * @return \Zend_Db_Expr|false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 101.0.0 @see \Magento\TargetRule\Model\Actions\Condition\Product\Attributes\SqlBuilder
     */
    public function getConditionForCollection($collection, $object, &$bind)
    {
        return $this->sqlBuilder->generateWhereClause($this, $bind, $object->getStoreId(), $object->select());
    }
}
