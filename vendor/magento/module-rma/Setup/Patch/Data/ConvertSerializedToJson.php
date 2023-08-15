<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldToConvert;
use Magento\Rma\Setup\RmaSetup;
use Magento\Rma\Setup\SerializedDataConverter;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Rma\Setup\RmaSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertSerializedToJson implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var AggregatedFieldDataConverter
     */
    private $aggregatedFieldConverter;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AggregatedFieldDataConverter $aggregatedFieldConverter
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $this->convertSerializedToJson();
    }

    /**
     * Convert serialized to JSON-encoded data
     *
     * @return void
     */
    private function convertSerializedToJson()
    {
        $fields = [
            new FieldToConvert(
                SerializedDataConverter::class,
                $this->moduleDataSetup->getTable('magento_rma_item_entity'),
                'entity_id',
                'product_options'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_rma_shipping_label'),
                'entity_id',
                'packages'
            ),
        ];
        $this->aggregatedFieldConverter->convert($fields, $this->moduleDataSetup->getConnection());
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
            ConvertRmaItemSerializedData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.4';
    }
}
