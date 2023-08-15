<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\BundleStaging\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class SynchronizeSequenceBundleOption implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        // Synchronizing the 'sequence_product_bundle_option' table.
        $connection->query(
            $connection->insertFromSelect(
                $connection->select()
                    ->distinct()
                    ->from(
                        $this->moduleDataSetup->getTable('catalog_product_bundle_option'),
                        ['option_id']
                    ),
                $this->moduleDataSetup->getTable('sequence_product_bundle_option'),
                ['sequence_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
            )
        );

        // Synchronizing the 'sequence_product_bundle_selection' table.
        $connection->query(
            $connection->insertFromSelect(
                $connection->select()->from(
                    $this->moduleDataSetup->getTable('catalog_product_bundle_selection'),
                    new \Zend_Db_Expr('DISTINCT `selection_id`')
                ),
                $this->moduleDataSetup->getTable('sequence_product_bundle_selection'),
                ['sequence_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
            )
        );
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
        return '2.0.1';
    }
}
