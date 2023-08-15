<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AsyncOrder\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Initializes status received for async orders.
 */
class InitOrderNewStatuses implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * InitStatusReceived constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        /**
         * Add received and rejected status
         */
        $this->moduleDataSetup->getConnection('sales')->insertMultiple(
            $this->moduleDataSetup->getTable('sales_order_status'),
            [
                ['status' => 'received', 'label' => __('Received')],
                ['status' => 'rejected', 'label' => __('Rejected')],
            ]
        );

        /**
         * Add received and rejected status state
         */
        $this->moduleDataSetup->getConnection('sales')->insertMultiple(
            $this->moduleDataSetup->getTable('sales_order_status_state'),
            [
                [
                    'status' => 'received',
                    'state' => 'received',
                    'is_default' => 1,
                    'visible_on_front' => 1,
                ],
                [
                    'status' => 'rejected',
                    'state' => 'canceled',
                    'is_default' => 1,
                    'visible_on_front' => 1,
                ]
            ]
        );

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public static function getVersion()
    {
        return '2.0.0';
    }
}
