<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddCustomerSegmentAttributes implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CollectionFactory $collectionFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CollectionFactory $collectionFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->collectionFactory = $collectionFactory;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        // use specific attributes for customer segments
        $attributesOfEntities = [
            'customer' => [
                'dob',
                'email',
                'firstname',
                'group_id',
                'lastname',
                'gender',
                'default_billing',
                'default_shipping',
                'created_at',
            ],
            'customer_address' => [
                'firstname',
                'lastname',
                'company',
                'street',
                'city',
                'region_id',
                'postcode',
                'country_id',
                'telephone',
            ],
            'order_address' => [
                'firstname',
                'lastname',
                'company',
                'street',
                'city',
                'region_id',
                'postcode',
                'country_id',
                'telephone',
                'email',
            ],
        ];

        foreach ($attributesOfEntities as $entityTypeId => $attributes) {
            foreach ($attributes as $attributeCode) {
                $eavSetup->updateAttribute(
                    $entityTypeId,
                    $attributeCode,
                    'is_used_for_customer_segment',
                    '1'
                );
            }
        }

        /**
         * Resave all segments for segment conditions regeneration
         */
        $collection = $this->collectionFactory->create();
        /** @var $segment \Magento\CustomerSegment\Model\Segment */
        foreach ($collection as $segment) {
            $segment->afterLoad();
            $segment->save();
        }

        $installer = $this->moduleDataSetup->createMigrationSetup();

        $installer->appendClassAliasReplace(
            'magento_customersegment_segment',
            'conditions_serialized',
            \Magento\Framework\Module\Setup\Migration::ENTITY_TYPE_MODEL,
            \Magento\Framework\Module\Setup\Migration::FIELD_CONTENT_TYPE_SERIALIZED,
            ['segment_id']
        );

        $installer->doUpdateClassAliases();
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
