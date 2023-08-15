<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Setup\Patch\Data;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\DB\FieldDataConverterFactory as ConverterFactory;
use Magento\Framework\DB\DataConverter\SerializedToJson as Converter;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class ConvertDataFromSerializedToJson implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ConverterFactory
     */
    private $converterFactory;

    /**
     * @var State
     */
    private $state;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ConverterFactory $converterFactory
     * @param State|null $state
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ConverterFactory $converterFactory,
        State $state = null
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->converterFactory = $converterFactory;
        $this->state = $state ?: ObjectManager::getInstance()->get(State::class);
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $converter = $this->converterFactory->create(Converter::class);
        $converter->convert(
            $this->moduleDataSetup->getConnection(),
            $this->moduleDataSetup->getTable('magento_customersegment_segment'),
            'segment_id',
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
            AddCustomerSegmentAttributes::class
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
