<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Model\Banner;

use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Store\Model\Store;
use Magento\Framework\App\RequestInterface;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\ModifierPoolDataProvider
{
    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Processor
     */
    private $processor;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $collectionFactory
     * @param RequestInterface $request,
     * @param Processor $processor,
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\Banner\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
        RequestInterface $request,
        Processor $processor,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null
    ) {
        $this->collection = $collectionFactory->create();
        $this->request = $request;
        $this->processor = $processor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
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
        /** @var \Magento\Banner\Model\Banner $banner */
        $banner = $this->collection->getFirstItem();

        $this->loadedData[$banner->getId()] = $this->processor->processData($banner, $storeId);

        return $this->loadedData;
    }
}
