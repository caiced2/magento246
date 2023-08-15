<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Setup\Patch\Data;

use Magento\CatalogStaging\Setup\CatalogProductSetup;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\CatalogStaging\Setup\CatalogProductSetupFactory;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MigrateCatalogProducts implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var \Magento\Staging\Api\Data\UpdateInterfaceFactory
     */
    private $updateFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \Magento\Staging\Model\VersionManagerFactory
     */
    private $versionManagerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CatalogProductSetupFactory
     */
    private $catalogProductSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CatalogProductSetupFactory $catalogProductSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Staging\Api\Data\UpdateInterfaceFactory $updateFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Framework\App\State $state,
        \Magento\Staging\Model\VersionManagerFactory $versionManagerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CatalogProductSetupFactory $catalogProductSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->updateRepository = $updateRepository;
        $this->updateFactory = $updateFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->state = $state;
        $this->versionManagerFactory = $versionManagerFactory;
        $this->storeManager = $storeManager;
        $this->catalogProductSetupFactory = $catalogProductSetupFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        // Emulate area for category migration
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateCategories'],
            []
        );
        // Emulate area for products migration
        $this->state->emulateAreaCode(
            \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE,
            [$this, 'updateProducts'],
            [$this->moduleDataSetup]
        );

        $this->migrateCatalogProducts($this->moduleDataSetup);
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
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
    public static function getVersion()
    {
        return '2.0.0';
    }

    /**
     * Fill in fields, created for staging support, with default values.
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    public function updateProducts(ModuleDataSetupInterface $setup)
    {
        $existingProductIdsSelect = $setup->getConnection()->select()
            ->from($setup->getTable('catalog_product_entity'), ['sequence_value' => 'row_id'])
            ->setPart('disable_staging_preview', true);

        $setup->getConnection()->query(
            $setup->getConnection()->insertFromSelect(
                $existingProductIdsSelect,
                $setup->getTable('sequence_product'),
                ['sequence_value'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_IGNORE
            )
        );

        $setup->getConnection()->update(
            $setup->getTable('catalog_product_entity'),
            [
                'entity_id' => new \Zend_Db_Expr('row_id'),
                'created_in' => \Magento\Staging\Model\VersionManager::MIN_VERSION,
                'updated_in' => \Magento\Staging\Model\VersionManager::MAX_VERSION
            ]
        );
    }

    /**
     * Update Categories
     *
     * @return void
     */
    public function updateCategories()
    {
        $categories = $this->categoryCollectionFactory->create()->addFieldToFilter('parent_id', ['neq' => '0']);

        $this->updateCategoriesScheduleTime($categories);
    }

    /**
     * Update categories schedule time
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categories
     * @return void
     */
    private function updateCategoriesScheduleTime(
        \Magento\Catalog\Model\ResourceModel\Category\Collection $categories
    ) {
        $allStoreId = $this->storeManager->getStores(true);
        $versionManager = $this->versionManagerFactory->create();
        foreach ($categories as $category) {
            foreach ($allStoreId as $storeId) {

                /** @var $category \Magento\Catalog\Model\Category */
                $category->setStoreId($storeId);
                $category->load($category['id']);

                if ($this->checkCategoryForUpdate($category)) {
                    if (!$this->isCategoryCustomDesignExpired($category)) {
                        $versionManager->getVersion()->setId(
                            $this->updateCustomDesignDateFields($category)->getId()
                        );
                    }

                    $category->setData('custom_design_from');
                    $category->setData('custom_design_to');
                    $category->save();
                }
            }
        }
    }

    /**
     * Check category for updaet
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return bool
     */
    private function checkCategoryForUpdate(\Magento\Catalog\Model\Category $category)
    {
        return $this->hasCategoryValueInNotDefaultStore($category) || $this->hasCategoryValueInDefaultStore($category);
    }

    /**
     * Has category value in not defaultt store
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return bool
     */
    private function hasCategoryValueInNotDefaultStore(\Magento\Catalog\Model\Category $category)
    {
        return $category->getExistsStoreValueFlag('custom_design_from')
            || $category->getExistsStoreValueFlag('custom_design_to');
    }

    /**
     * Has category value in default store
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return bool
     */
    private function hasCategoryValueInDefaultStore(\Magento\Catalog\Model\Category $category)
    {
        return ($category->getStoreId() === \Magento\Store\Model\Store::DEFAULT_STORE_ID
            && $category->getData('custom_design_to')
            || $category->getStoreId() === \Magento\Store\Model\Store::DEFAULT_STORE_ID
            && $category->getData('custom_design_from'));
    }

    /**
     * Checks whether the category custom design has expired or not.
     *
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return bool
     */
    private function isCategoryCustomDesignExpired(\Magento\Catalog\Model\Category $category)
    {
        if ($category->getData('custom_design_to')) {
            $dateTo = new \DateTime($category->getData('custom_design_to'), new \DateTimeZone('UTC'));

            if ($dateTo->getTimestamp() <= (new \DateTime())->getTimestamp()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update custom design date field
     *
     * @param \Magento\Catalog\Model\Category $category
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    private function updateCustomDesignDateFields(\Magento\Catalog\Model\Category $category)
    {
        /** @var \Magento\Staging\Api\Data\UpdateInterface $update */
        $update = $this->updateFactory->create();

        $update->setName($category->getData('name'));

        $dateNow = new \DateTime('now', new \DateTimeZone('UTC'));

        $dateFrom = $category->getData('custom_design_from') ?: 'now';
        $dateFrom = new \DateTime($dateFrom, new \DateTimeZone('UTC'));

        if ($dateFrom->getTimestamp() < $dateNow->getTimestamp()) {
            $dateFrom = $dateNow;
        }

        $update->setStartTime($dateFrom->format('Y-m-d H:i:s'));

        if ($category->getData('custom_design_to')) {
            $dateTo = new \DateTime($category->getData('custom_design_to'), new \DateTimeZone('UTC'));

            $update->setEndTime($dateTo->format('Y-m-d 23:59:59'));
        }

        $update->setIsCampaign(false);

        $this->updateRepository->save($update);

        return $update;
    }

    /**
     * Migrate catalog products
     *
     * @param ModuleDataSetupInterface $setup
     * @return void
     */
    private function migrateCatalogProducts(ModuleDataSetupInterface $setup)
    {
        /** @var CatalogProductSetup $catalogProductSetup */
        $catalogProductSetup = $this->catalogProductSetupFactory->create();
        $catalogProductSetup->execute($setup);
    }
}
