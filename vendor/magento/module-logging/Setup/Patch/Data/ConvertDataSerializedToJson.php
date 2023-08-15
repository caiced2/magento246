<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Setup\Patch\Data;

use Magento\Framework\DB\AggregatedFieldDataConverter;
use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldToConvert;
use Magento\Framework\DB\Select\QueryModifierFactory;
use Magento\Logging\Setup\ObjectConverter;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertDataSerializedToJson implements
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
     * @var QueryModifierFactory
     */
    private $queryModifierFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AggregatedFieldDataConverter $aggregatedFieldConverter
     * @param QueryModifierFactory $queryModifierFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AggregatedFieldDataConverter $aggregatedFieldConverter,
        QueryModifierFactory $queryModifierFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->aggregatedFieldConverter = $aggregatedFieldConverter;
        $this->queryModifierFactory = $queryModifierFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $fields = [
            new FieldToConvert(
                ObjectConverter::class,
                $this->moduleDataSetup->getTable('magento_logging_event'),
                'log_id',
                'info'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_logging_event_changes'),
                'id',
                'original_data'
            ),
            new FieldToConvert(
                SerializedToJson::class,
                $this->moduleDataSetup->getTable('magento_logging_event_changes'),
                'id',
                'result_data'
            ),
        ];
        $queryModifier = $this->queryModifierFactory->create(
            'in',
            [
                'values' => [
                    'path' => [
                        'admin/magento_logging/actions',
                    ]
                ]
            ]
        );
        $fields[] = new FieldToConvert(
            SerializedToJson::class,
            $this->moduleDataSetup->getTable('core_config_data'),
            'config_id',
            'value',
            $queryModifier
        );

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

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.2';
    }
}
