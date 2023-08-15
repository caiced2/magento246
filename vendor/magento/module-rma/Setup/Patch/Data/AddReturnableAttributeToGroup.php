<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class AddReturnableAttributeToGroup
 */
class AddReturnableAttributeToGroup implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var \Magento\Framework\Setup\ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Rma\Setup\RmaSetupFactory
     */
    private $rmaSetupFactory;

    /**
     * PatchInitial constructor.
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Rma\Setup\RmaSetupFactory $rmaSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->rmaSetupFactory = $rmaSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var \Magento\Rma\Setup\RmaSetup $rmaSetup */
        $rmaSetup = $this->rmaSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $rmaSetup->addReturnableAttributeToGroup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            UpdateRmaEntityTypes::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
