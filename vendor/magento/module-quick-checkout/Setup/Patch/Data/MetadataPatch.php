<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\QuickCheckout\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\QuickCheckout\Setup\MetadataData;

class MetadataPatch implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var MetadataData
     */
    private $metadataData;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param MetadataData $metadataData
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, MetadataData $metadataData)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->metadataData = $metadataData;
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
     * Inherit apply method and implement it
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->metadataData->saveInstallationDate(time()); //Save server time when installation is done
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $this->metadataData->clearInstallationDate();
        $this->moduleDataSetup->getConnection()->endSetup();
    }
}
