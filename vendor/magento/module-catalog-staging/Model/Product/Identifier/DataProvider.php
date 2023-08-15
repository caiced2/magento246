<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Identifier;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\RequestInterface;

class DataProvider extends \Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider
{
    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @inheritDoc
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        PoolInterface $pool,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $collectionFactory, $pool, $meta, $data);
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $storeId = $this->request->getParam('store', Store::DEFAULT_STORE_ID);

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($this->collection->getItems() as $product) {
            $this->loadedData[$product->getId()] = [
                'entity_id' => $product->getId(),
                'name' => $product->getName(),
                'store_id' => $storeId,
            ];
        }

        return $this->loadedData;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getMeta()
    {
        return [];
    }
}
