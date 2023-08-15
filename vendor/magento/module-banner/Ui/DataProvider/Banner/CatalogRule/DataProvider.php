<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Ui\DataProvider\Banner\CatalogRule;

use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\Banner\Model\BannerFactory;

/**
 * Data provider for banner_catalog_rule_listing.xml
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Banner model
     *
     * @var \Magento\Banner\Model\BannerFactory
     */
    private $bannerFactory = null;

    /**
     * DataProvider constructor.
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param BannerFactory $bannerFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        BannerFactory $bannerFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->bannerFactory = $bannerFactory;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $collection = $this->getCollection();
        return $collection->toArray();
    }

    /**
     * Get related banners by current rule
     *
     * @param int $bannerId
     * @return array
     */
    public function getRelatedSalesRuleByBanner($bannerId)
    {
        return $this->bannerFactory->create()->getRelatedCatalogRule($bannerId);
    }
}
