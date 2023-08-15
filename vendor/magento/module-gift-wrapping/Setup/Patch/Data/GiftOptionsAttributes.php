<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftWrapping\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Quote\Setup\QuoteSetupFactory;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class GiftOptionsAttributes implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var QuoteSetupFactory
     */
    private $quoteSetupFactory;

    /**
     * @var ConfigInterface
     */
    private $productTypeConfig;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SalesSetupFactory $salesSetupFactory
     * @param QuoteSetupFactory $quoteSetupFactory
     * @param ConfigInterface $productTypeConfig
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SalesSetupFactory $salesSetupFactory,
        QuoteSetupFactory $quoteSetupFactory,
        ConfigInterface $productTypeConfig,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->quoteSetupFactory = $quoteSetupFactory;
        $this->productTypeConfig = $productTypeConfig;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $salesInstaller = $this->salesSetupFactory->create(
            [
                'resourceName' => 'sales_setup',
                'setup' => $this->moduleDataSetup
            ]
        );

        /**
         * Add gift wrapping attributes for catalog product entity
         */
        $applyTo = join(',', $this->productTypeConfig->filter('is_real_product'));

        /** @var CategorySetup  $installer*/
        $installer = $this->categorySetupFactory->create(
            [
                'resourceName' => 'catalog_setup',
                'setup' => $this->moduleDataSetup
            ]
        );

        $installer->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'gift_wrapping_available',
            [
                'group' => 'Gift Options',
                'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
                'frontend' => '',
                'label' => 'Allow Gift Wrapping',
                'input' => 'select',
                'source' => \Magento\Catalog\Model\Product\Attribute\Source\Boolean::class,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'apply_to' => $applyTo,
                'frontend_class' => 'hidden-for-virtual',
                'frontend_input_renderer' => \Magento\GiftWrapping\Block\Adminhtml\Product\Helper\Form\Config::class,
                'input_renderer' => \Magento\GiftWrapping\Block\Adminhtml\Product\Helper\Form\Config::class,
                'visible_on_front' => false
            ]
        );

        $installer->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'gift_wrapping_price',
            [
                'group' => 'Gift Options',
                'type' => 'decimal',
                'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
                'frontend' => '',
                'label' => 'Price for Gift Wrapping',
                'input' => 'price',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'apply_to' => $applyTo,
                'frontend_class' => 'hidden-for-virtual',
                'visible_on_front' => false
            ]
        );

        $groupName = 'Autosettings';
        $entityTypeId = $salesInstaller->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetId = $salesInstaller->getAttributeSetId($entityTypeId, 'Default');

        $attributesOrder = ['gift_wrapping_available' => 70, 'gift_wrapping_price' => 80];

        foreach ($attributesOrder as $key => $value) {
            $attribute = $salesInstaller->getAttribute($entityTypeId, $key);
            if ($attribute) {
                $salesInstaller->addAttributeToGroup(
                    $entityTypeId,
                    $attributeSetId,
                    $groupName,
                    $attribute['attribute_id'],
                    $value
                );
            }
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
}
