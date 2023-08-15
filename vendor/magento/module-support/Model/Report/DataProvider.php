<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * DataProvider for system report form
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Config for system report
     *
     * @var Config
     */
    protected $reportConfig;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Config $reportConfig
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Config $reportConfig,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->reportConfig = $reportConfig;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Return data for system report form
     *
     * @return array
     */
    public function getData()
    {
        return [null => ['general' => ['report_groups' => $this->reportConfig->getGroupNames()]]];
    }
}
