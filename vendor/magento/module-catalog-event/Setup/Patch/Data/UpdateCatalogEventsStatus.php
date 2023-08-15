<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class UpdateCatalogEventsStatus implements
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
        $select = $this->moduleDataSetup->getConnection()->select()
            ->from(
                false,
                [
                    'event_id',
                    'category_id',
                    'date_start',
                    'date_end',
                    'display_state',
                    'sort_order',
                    'status' => new \Zend_Db_Expr(
                        'if ('
                        . 'date_start <= CURRENT_TIMESTAMP() AND date_end >= CURRENT_TIMESTAMP(), '
                        . '0, '
                        . 'if (date_end < CURRENT_TIMESTAMP(), 2, 1)'
                        . ')'
                    ),
                ]
            );
        $select = $this->moduleDataSetup->getConnection()->updateFromSelect(
            $select,
            $this->moduleDataSetup->getTable('magento_catalogevent_event')
        );
        $this->moduleDataSetup->getConnection()->query($select);
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
            EventsListenerCmsBlock::class
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
