<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Rma\Setup\RmaSetup;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Rma\Setup\RmaSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateRmaEntityTypes implements
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
    private $setupFactory;

    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @var AggregatedFieldDataConverter
     */
    private $aggregatedFieldConverter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RmaSetupFactory $setupFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RmaSetupFactory $setupFactory,
        \Magento\Eav\Model\Config $eavConfig,
        AggregatedFieldDataConverter $aggregatedFieldConverter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->setupFactory = $setupFactory;
        $this->eavConfig = $eavConfig;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        //Add Product's Attribute
        /** @var RmaSetup $installer */
        $installer = $this->setupFactory->create(['setup' => $this->moduleDataSetup]);
        $installer->updateEntityTypes();
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
            AddRmaAttributes::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.1';
    }
}
