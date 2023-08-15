<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Stores;

use Magento\Support\Model\Report\Group\AbstractSection;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManager;
use Magento\Catalog\Model\ResourceModel\Category\Collection\Factory as CategoryCollectionFactory;

/**
 * Abstract section for all Stores Report sections
 */
abstract class AbstractStoresSection extends AbstractSection
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection\Factory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Magento\Store\Model\Website[]|null
     */
    protected $websites;

    /**
     * @var \Magento\Store\Model\Group[]|null
     */
    protected $stores;

    /**
     * @var \Magento\Store\Model\Store[]|null
     */
    protected $storeViews;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection|null
     */
    protected $rootCategoryCollection;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection\Factory $categoryCollectionFactory
     * @param array $data
     */
    public function __construct(
        LoggerInterface $logger,
        StoreManager $storeManager,
        CategoryCollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($logger, $data);
    }

    /**
     * Get websites
     *
     * @return \Magento\Store\Model\Website[]
     */
    protected function getWebsites()
    {
        if ($this->websites === null) {
            $this->websites = $this->storeManager->getWebsites();
        }
        return $this->websites;
    }

    /**
     * Get stores
     *
     * @return \Magento\Store\Model\Group[]
     */
    protected function getStores()
    {
        if ($this->stores === null) {
            $this->stores = $this->storeManager->getGroups();
        }
        return $this->stores;
    }

    /**
     * Get store views
     *
     * @return \Magento\Store\Model\Store[]
     */
    protected function getStoreViews()
    {
        if ($this->storeViews === null) {
            $this->storeViews = $this->storeManager->getStores();
        }
        return $this->storeViews;
    }

    /**
     * Get root collection of root categories
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected function getRootCategoryCollection()
    {
        if ($this->rootCategoryCollection === null) {
            $this->rootCategoryCollection = $this->categoryCollectionFactory->create();
            $this->rootCategoryCollection->addNameToResult()
                ->addRootLevelFilter()
                ->load();
        }
        return $this->rootCategoryCollection;
    }
}
