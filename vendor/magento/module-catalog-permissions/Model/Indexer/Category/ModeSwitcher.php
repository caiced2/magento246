<?php
declare(strict_types=1);

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Model\Indexer\Category;

use Magento\Customer\Model\Indexer\CustomerGroupDimensionProvider;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Indexer\Model\DimensionMode;
use Magento\Indexer\Model\DimensionModes;
use Magento\CatalogPermissions\Model\Indexer\TableMaintainer;
use Magento\Indexer\Model\Indexer;

class ModeSwitcher implements \Magento\Indexer\Model\ModeSwitcherInterface
{
    /**
     * @var TableMaintainer
     */
    private $tableMaintainer;

    /**
     * TypeListInterface
     *
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**#@+
     * Available modes of dimensions for product price indexer
     */
    public const DIMENSION_NONE = 'none';
    public const DIMENSION_CUSTOMER_GROUP = 'customer_group';

    public const XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE = 'indexer/catalogpermissions_category/dimensions_mode';

    /**
     * ConfigInterface
     *
     * @var ConfigInterface
     */
    private $configWriter;

    /**
     * @var Indexer $indexer
     */
    private $indexer;

    /**
     * Mapping between dimension mode and dimension provider name
     *
     * @var array
     */
    private $modesMapping = [
        self::DIMENSION_NONE => [
        ],
        self::DIMENSION_CUSTOMER_GROUP => [
            CustomerGroupDimensionProvider::DIMENSION_NAME
        ],
    ];

    /**
     * @param TableMaintainer $tableMaintainer
     * @param ConfigInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Indexer $indexer
     */
    public function __construct(
        TableMaintainer $tableMaintainer,
        ConfigInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Indexer $indexer
    ) {
        $this->tableMaintainer = $tableMaintainer;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->indexer = $indexer;
    }
    /**
     * @inheritdoc
     */
    public function getDimensionModes(): DimensionModes
    {
        $dimensionsList = [];
        foreach ($this->modesMapping as $dimension => $modes) {
            $dimensionsList[] = new DimensionMode($dimension, $modes);
        }

        return new DimensionModes($dimensionsList);
    }

    /**
     * @inheritdoc
     */
    public function switchMode(string $currentMode, string $previousMode)
    {
        $this->tableMaintainer->createTablesForCurrentMode($currentMode);
        $this->saveMode($currentMode);
        $this->tableMaintainer->dropOldData($currentMode);
    }

    /**
     * Save Dimensions mode
     *
     * @param string $mode
     * @return void
     */
    private function saveMode($mode)
    {
        $this->configWriter->saveConfig(self::XML_PATH_CATEGORY_PERMISSION_DIMENSIONS_MODE, $mode);
        $this->cacheTypeList->cleanType('config');
        $this->indexer->load(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID);
        $this->indexer->invalidate();
        $this->indexer->load(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID);
        $this->indexer->invalidate();
    }
}
