<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CmsStaging\Setup\Patch\Data;

use Magento\CmsStaging\Setup\CmsSetup;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\CmsStaging\Setup\CmsSetupFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class MigrateCms implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CmsSetupFactory
     */
    private $cmsSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CmsSetupFactory $cmsSetupFactory
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup, CmsSetupFactory $cmsSetupFactory)
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->cmsSetupFactory = $cmsSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        /** @var CmsSetup $cmsSetup */
        $cmsSetup = $this->cmsSetupFactory->create();
        $cmsSetup->execute($this->moduleDataSetup);
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
