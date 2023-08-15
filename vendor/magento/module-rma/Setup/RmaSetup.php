<?php
/**
 * Catalog entity setup
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Setup;

use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;

/**
 * @codeCoverageIgnore
 */
class RmaSetup extends EavSetup
{
    /**
     * Retrieve default RMA item entities
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDefaultEntities()
    {
        $entities = [
            'rma_item' => [
                'entity_model' => \Magento\Rma\Model\ResourceModel\Item::class,
                'attribute_model' => \Magento\Rma\Model\Item\Attribute::class,
                'table' => 'magento_rma_item_entity',
                'increment_model' => \Magento\Eav\Model\Entity\Increment\NumericValue::class,
                'additional_attribute_table' => 'magento_rma_item_eav_attribute',
                'entity_attribute_collection' => null,
                'increment_per_store' => 1,
                'attributes' => [
                    'rma_entity_id' => [
                        'type' => 'static',
                        'label' => 'RMA Id',
                        'input' => 'text',
                        'required' => true,
                        'visible' => false,
                        'sort_order' => 10,
                        'position' => 10,
                    ],
                    'order_item_id' => [
                        'type' => 'static',
                        'label' => 'Order Item Id',
                        'input' => 'text',
                        'required' => true,
                        'visible' => false,
                        'sort_order' => 20,
                        'position' => 20,
                    ],
                    'qty_requested' => [
                        'type' => 'static',
                        'label' => 'Qty of requested for RMA items',
                        'input' => 'text',
                        'required' => true,
                        'visible' => false,
                        'sort_order' => 30,
                        'position' => 30,
                    ],
                    'qty_authorized' => [
                        'type' => 'static',
                        'label' => 'Qty of authorized items',
                        'input' => 'text',
                        'visible' => false,
                        'sort_order' => 40,
                        'position' => 40,
                    ],
                    'qty_approved' => [
                        'type' => 'static',
                        'label' => 'Qty of requested for RMA items',
                        'input' => 'text',
                        'visible' => false,
                        'sort_order' => 50,
                        'position' => 50,
                    ],
                    'status' => [
                        'type' => 'static',
                        'label' => 'Status',
                        'input' => 'select',
                        'source' => \Magento\Rma\Model\Item\Attribute\Source\Status::class,
                        'visible' => false,
                        'sort_order' => 60,
                        'position' => 60,
                        'adminhtml_only' => 1,
                    ],
                    'product_name' => [
                        'type' => 'static',
                        'label' => 'Product Name',
                        'input' => 'text',
                        'sort_order' => 70,
                        'position' => 70,
                        'visible' => false,
                        'adminhtml_only' => 1,
                    ],
                    'product_sku' => [
                        'type' => 'static',
                        'label' => 'Product SKU',
                        'input' => 'text',
                        'sort_order' => 80,
                        'position' => 80,
                        'visible' => false,
                        'adminhtml_only' => 1,
                    ],
                    'resolution' => [
                        'type' => 'int',
                        'label' => 'Resolution',
                        'input' => 'select',
                        'sort_order' => 90,
                        'position' => 90,
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                        'system' => false,
                        'option' => ['values' => ['Exchange', 'Refund', 'Store Credit']],
                        'validate_rules' => '[]',
                    ],
                    'condition' => [
                        'type' => 'int',
                        'label' => 'Item Condition',
                        'input' => 'select',
                        'sort_order' => 100,
                        'position' => 100,
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                        'system' => false,
                        'option' => ['values' => ['Unopened', 'Opened', 'Damaged']],
                        'validate_rules' => '[]',
                    ],
                    'reason' => [
                        'type' => 'int',
                        'label' => 'Reason to Return',
                        'input' => 'select',
                        'sort_order' => 110,
                        'position' => 110,
                        'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
                        'system' => false,
                        'option' => ['values' => ['Wrong Color', 'Wrong Size', 'Out of Service']],
                        'validate_rules' => '[]',
                    ],
                    'reason_other' => [
                        'type' => 'varchar',
                        'label' => 'Other',
                        'input' => 'text',
                        'validate_rules' => '{"max_text_length":255,"min_text_length":1}',
                        'sort_order' => 120,
                        'position' => 120,
                    ],
                ],
            ],
        ];
        return $entities;
    }

    /**
     * Update entity types for 'entity_model' and 'increment_model'
     *
     * @return void
     */
    public function updateEntityTypes()
    {
        $this->updateEntityType(
            \Magento\Rma\Model\Item::ENTITY,
            'entity_model',
            \Magento\Rma\Model\ResourceModel\Item::class
        );

        $this->updateEntityType(
            \Magento\Rma\Model\Item::ENTITY,
            'increment_model',
            \Magento\Eav\Model\Entity\Increment\NumericValue::class
        );
    }

    /**
     * Add 'is_returnable' attribute to the product group
     *
     * @return void
     */
    public function addReturnableAttributeToGroup()
    {
        $attributeSetId = $this->getDefaultAttributeSetId(Product::ENTITY);
        $this->addAttributeToGroup(Product::ENTITY, $attributeSetId, 'Product Details', 'is_returnable', 120);
    }
}
