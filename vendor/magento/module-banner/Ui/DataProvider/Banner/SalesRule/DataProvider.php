<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Ui\DataProvider\Banner\SalesRule;

use Magento\SalesRule\Model\ResourceModel\Rule\Quote\CollectionFactory;
use Magento\Banner\Model\BannerFactory;

/**
 * Data provider for Magento_Banner::sales_rule_listing.xml
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
    /**
     * @return array
     */
    public function getData()
    {
        return $this->collection->toArray();
    }
}
