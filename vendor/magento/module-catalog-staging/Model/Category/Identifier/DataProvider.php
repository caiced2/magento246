<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Category\Identifier;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\DataProvider as CatalogCategoryDataProvider;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\App\RequestInterface $request
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Framework\App\RequestInterface $request,
        CategoryCollectionFactory $categoryCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->collection = $categoryCollectionFactory->create();
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $storeId = $this->request->getParam('store', Store::DEFAULT_STORE_ID);
        /** @var  \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->collection;
        $collection->setStore($storeId);
        $items = $this->collection->getItems();
        /** @var Category $category */
        foreach ($items as $category) {
            /** @var Category $category */
            $this->loadedData[$category->getId()] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'store_id' => $storeId,
            ];
        }

        return $this->loadedData;
    }
}
