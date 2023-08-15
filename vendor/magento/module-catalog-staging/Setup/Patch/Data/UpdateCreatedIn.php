<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateCreatedIn implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var State
     */
    private $state;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param State $state
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, State $state)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->state = $state;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        // Emulate area for products update
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateProducts'],
            [$this->moduleDataSetup]
        );
    }

    /**
     * Change 'created_in' value from 0 => 1 to allow product editing.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function updateProducts(ModuleDataSetupInterface $setup)
    {
        $setup->startSetup();
        $setup->getConnection()->update(
            $setup->getTable('catalog_product_entity'),
            [
                'created_in' => \Magento\Staging\Model\VersionManager::MIN_VERSION,
            ],
            ['created_in = ?' => 0]
        );
        $setup->endSetup();
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
            MigrateCatalogProducts::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.1.0';
    }
}
