<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Setup\Patch\Data;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Rma\Setup\RmaSetup;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\SalesSequence\Model\Builder;
use Magento\Rma\Setup\RmaSetupFactory;
use Magento\SalesSequence\Model\Config as SequenceConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddRmaAttributes implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var RmaSetupFactory
     */
    private $rmaSetupFactory;

    /**
     * @var ConfigInterface
     */
    private $productTypeConfig;

    /**
     * @var Builder
     */
    private $sequenceBuilder;

    /**
     * @var SequenceConfig
     */
    private $sequenceConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RmaSetupFactory $setupFactory
     * @param ConfigInterface $productTypeConfig
     * @param Builder $sequenceBuilder
     * @param SequenceConfig $sequenceConfig
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RmaSetupFactory $setupFactory,
        ConfigInterface $productTypeConfig,
        Builder $sequenceBuilder,
        SequenceConfig $sequenceConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->rmaSetupFactory = $setupFactory;
        $this->productTypeConfig = $productTypeConfig;
        $this->sequenceBuilder = $sequenceBuilder;
        $this->sequenceConfig = $sequenceConfig;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function apply()
    {
        //Add Product's Attribute
        /** @var RmaSetup $installer */
        $installer = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);

        /**
         * Prepare database before module installation
         */
        $installer->installEntities();
        $this->installForms($this->moduleDataSetup, $installer);

        $installer->addAttribute(
            'rma_item',
            'qty_returned',
            [
                'type' => 'static',
                'label' => 'Qty of returned items',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 45,
                'position' => 45
            ]
        );

        $installer->addAttribute(
            'rma_item',
            'product_admin_name',
            [
                'type' => 'static',
                'label' => 'Product Name For Backend',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 46,
                'position' => 46
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'product_admin_sku',
            [
                'type' => 'static',
                'label' => 'Product Sku For Backend',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 47,
                'position' => 47
            ]
        );
        $installer->addAttribute(
            'rma_item',
            'product_options',
            [
                'type' => 'static',
                'label' => 'Product Options',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 48,
                'position' => 48
            ]
        );

        /* setting is_qty_decimal field in rma_item_entity table as a static attribute */
        $installer->addAttribute(
            'rma_item',
            'is_qty_decimal',
            [
                'type' => 'static',
                'label' => 'Is item quantity decimal',
                'input' => 'text',
                'visible' => false,
                'sort_order' => 15,
                'position' => 15
            ]
        );

        $installer->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'is_returnable',
            [
                'group' => 'Autosettings',
                'frontend' => '',
                'label' => 'Enable RMA',
                'input' => 'select',
                'class' => '',
                'source' => \Magento\Rma\Model\Product\Source::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => \Magento\Rma\Model\Product\Source::ATTRIBUTE_ENABLE_RMA_USE_CONFIG,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => implode(',', $this->getRefundableProducts()),
                'input_renderer' => \Magento\Rma\Block\Adminhtml\Product\Renderer::class
            ]
        );

        /** @var $migrationSetup \Magento\Framework\Module\Setup\Migration */
        $migrationSetup = $this->moduleDataSetup->createMigrationSetup();

        $migrationSetup->appendClassAliasReplace(
            'magento_rma_item_eav_attribute',
            'data_model',
            \Magento\Framework\Module\Setup\Migration::ENTITY_TYPE_MODEL,
            \Magento\Framework\Module\Setup\Migration::FIELD_CONTENT_TYPE_PLAIN,
            ['attribute_id']
        );
        $migrationSetup->doUpdateClassAliases();

        $groupName = 'Autosettings';
        $entityTypeId = $installer->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetId = $installer->getAttributeSetId($entityTypeId, 'Default');

        $attribute = $installer->getAttribute($entityTypeId, 'is_returnable');
        if ($attribute) {
            $installer->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupName,
                $attribute['attribute_id'],
                90
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * Add RMA Item Attributes to Forms
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Rma\Setup\RmaSetup $installer
     *
     * @return void
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function installForms(ModuleDataSetupInterface $setup, RmaSetup $installer)
    {
        $rmaItem = (int)$installer->getEntityTypeId('rma_item');

        $attributeIds = [];
        $select = $setup->getConnection()->select()->from(
            ['ea' => $setup->getTable('eav_attribute')],
            ['entity_type_id', 'attribute_code', 'attribute_id']
        )->where(
            'ea.entity_type_id = ?',
            $rmaItem
        );
        foreach ($setup->getConnection()->fetchAll($select) as $row) {
            $attributeIds[$row['entity_type_id']][$row['attribute_code']] = $row['attribute_id'];
        }

        $data = [];
        $entities = $installer->getDefaultEntities();
        $attributes = $entities['rma_item']['attributes'];
        foreach ($attributes as $attributeCode => $attribute) {
            $attributeId = $attributeIds[$rmaItem][$attributeCode];
            $attribute['system'] = isset($attribute['system']) ? $attribute['system'] : true;
            $attribute['visible'] = isset($attribute['visible']) ? $attribute['visible'] : true;
            if ($attribute['system'] != true || $attribute['visible'] != false) {
                $usedInForms = ['default'];
                foreach ($usedInForms as $formCode) {
                    $data[] = ['form_code' => $formCode, 'attribute_id' => $attributeId];
                }
            }
        }

        if ($data) {
            $setup->getConnection()->insertMultiple($setup->getTable('magento_rma_item_form_attribute'), $data);
        }
    }

    /**
     * Get refundable product types
     *
     * @return array
     */
    public function getRefundableProducts()
    {
        return array_diff(
            $this->productTypeConfig->filter('refundable'),
            $this->productTypeConfig->filter('is_product_set')
        );
    }
}
