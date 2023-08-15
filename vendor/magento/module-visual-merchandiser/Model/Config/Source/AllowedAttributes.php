<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Config\Source;

/**
 * Class AllowedAttributes
 * @package Magento\VisualMerchandiser\Model\Config\Source
 * @api
 * @since 100.0.2
 */
class AllowedAttributes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attribute;

    /**
     * @var \Magento\Eav\Model\Entity\Type
     */
    protected $type;

    /**
     * Options array
     *
     * @var array
     */
    protected $options;

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     * @param \Magento\Eav\Model\Entity\Type $type
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute,
        \Magento\Eav\Model\Entity\Type $type
    ) {
        $this->attribute = $attribute;
        $this->type = $type;
    }

    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $entityTypeId = $this->type->loadByCode(\Magento\Catalog\Model\Product::ENTITY)->getId();
        if ($entityTypeId) {
            $collection = $this->attribute->getCollection()
                ->removeAllFieldsFromSelect()
                ->addFieldToSelect('attribute_code', 'value')
                ->addFieldToSelect('frontend_label', 'label')
                ->addFieldToFilter('entity_type_id', ['eq' => $entityTypeId])
                ->addFieldToFilter('frontend_input', ['neq' => 'multiselect']);
            $attributes = $collection->toArray();
            if (isset($attributes['items'])) {
                $this->options = $attributes['items'];
            }
        }
        return $this->options;
    }
}
