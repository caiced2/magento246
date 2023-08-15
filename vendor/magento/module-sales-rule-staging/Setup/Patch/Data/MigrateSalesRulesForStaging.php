<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesRuleStaging\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class MigrateSalesRulesForStaging
 * @package Magento\SalesRuleStaging\Setup\Patch
 */
class MigrateSalesRulesForStaging implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\SalesRuleStaging\Setup\SalesRuleMigrationFactory
     */
    private $salesRuleMigrationFactory;

    /**
     * MigrateSalesRulesForStaging constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\SalesRuleStaging\Setup\SalesRuleMigrationFactory $salesRuleMigrationFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\SalesRuleStaging\Setup\SalesRuleMigrationFactory $salesRuleMigrationFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->salesRuleMigrationFactory = $salesRuleMigrationFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();
        /** @var \Magento\SalesRuleStaging\Setup\SalesRuleMigration $salesRuleMigration */
        $salesRuleMigration = $this->salesRuleMigrationFactory->create();
        // Migrate sales rules for staging
        $salesRuleMigration->migrateRules($this->moduleDataSetup);
        $this->moduleDataSetup->endSetup();
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
