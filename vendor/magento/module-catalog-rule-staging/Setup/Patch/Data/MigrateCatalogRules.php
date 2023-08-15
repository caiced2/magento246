<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogRuleStaging\Setup\Patch\Data;

use Magento\CatalogRuleStaging\Setup\CatalogRuleSetupFactory;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class MigrateCatalogRules implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CatalogRuleSetupFactory
     */
    private $catalogRuleSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CatalogRuleSetupFactory $catalogRuleSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->catalogRuleSetupFactory = $catalogRuleSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        /** @var \Magento\CatalogRuleStaging\Setup\CatalogRuleSetup $catalogRuleSetup */
        $catalogRuleSetup = $this->catalogRuleSetupFactory->create();

        // Migrate catalog rules for staging
        $catalogRuleSetup->migrateRules($this->moduleDataSetup);

        $this->moduleDataSetup->endSetup();
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
