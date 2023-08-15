<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Ui\DataProvider\CatalogRule;

use Magento\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Banner\Model\BannerFactory;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Banner model
     *
     * @var \Magento\Banner\Model\BannerFactory
     */
    protected $bannerFactory = null;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param RequestInterface $request
     * @param BannerFactory $bannerFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        BannerFactory $bannerFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->request = $request;
        $this->collection = $collectionFactory->create()->addStoresVisibility();
        $this->bannerFactory = $bannerFactory;
    }

    /**
     * @return array
     */
    public function getData()
    {
        if ($this->request->getParam('rule_id')) {
            $bannerIds = $this->getRelatedBannersByRule($this->request->getParam('rule_id'));
            return $this->getCollection()->addFieldToFilter('main_table.banner_id', ['in' => $bannerIds])->toArray();
        }
        return parent::getData();
    }

    /**
     * Get related banners by current rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getRelatedBannersByRule($ruleId)
    {
        return $this->bannerFactory->create()->getRelatedBannersByCatalogRuleId($ruleId);
    }
}
