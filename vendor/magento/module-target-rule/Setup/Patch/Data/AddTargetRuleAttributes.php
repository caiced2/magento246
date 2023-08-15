<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class AddTargetRuleAttributes
 * @package Magento\TargetRule\Setup\Patch
 */
class AddTargetRuleAttributes implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * AddTargetRuleAttributes constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var \Magento\Eav\Setup\EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        // add config attributes to catalog product
        $eavSetup->addAttribute(
            'catalog_product',
            'related_tgtr_position_limit',
            [
                'label' => 'Related Target Rule Rule Based Positions',
                'visible' => false,
                'user_defined' => false,
                'required' => false,
                'type' => 'int',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'input' => 'text',
                'backend' => \Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule::class
            ]
        );
        $eavSetup->addAttribute(
            'catalog_product',
            'related_tgtr_position_behavior',
            [
                'label' => 'Related Target Rule Position Behavior',
                'visible' => false,
                'user_defined' => false,
                'required' => false,
                'type' => 'int',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'input' => 'text',
                'backend' => \Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule::class
            ]
        );
        $eavSetup->addAttribute(
            'catalog_product',
            'upsell_tgtr_position_limit',
            [
                'label' => 'Upsell Target Rule Rule Based Positions',
                'visible' => false,
                'user_defined' => false,
                'required' => false,
                'type' => 'int',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'input' => 'text',
                'backend' => \Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule::class
            ]
        );
        $eavSetup->addAttribute(
            'catalog_product',
            'upsell_tgtr_position_behavior',
            [
                'label' => 'Upsell Target Rule Position Behavior',
                'visible' => false,
                'user_defined' => false,
                'required' => false,
                'type' => 'int',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'input' => 'text',
                'backend' => \Magento\TargetRule\Model\Catalog\Product\Attribute\Backend\Rule::class
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
