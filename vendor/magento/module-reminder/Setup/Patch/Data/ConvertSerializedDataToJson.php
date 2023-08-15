<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Setup\Patch\Data;

use Magento\Framework\DB\DataConverter\SerializedToJson;
use Magento\Framework\DB\FieldDataConverterFactory;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertSerializedDataToJson implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var FieldDataConverterFactory
     */
    private $factory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param FieldDataConverterFactory $factory
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, FieldDataConverterFactory $factory)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->factory = $factory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $fieldDataConverter = $this->factory->create(SerializedToJson::class);
        $fieldDataConverter->convert(
            $this->moduleDataSetup->getConnection(),
            $this->moduleDataSetup->getTable('magento_reminder_rule'),
            'rule_id',
            'conditions_serialized'
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
            AddReminderAttributes::class
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
