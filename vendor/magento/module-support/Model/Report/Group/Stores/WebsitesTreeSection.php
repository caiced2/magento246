<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Stores;

use Magento\Store\Model\Website;
use Magento\Store\Model\Group as Store;
use Magento\Store\Model\Store as StoreView;

/**
 * Websites Tree report section
 */
class WebsitesTreeSection extends AbstractStoresSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = [];
        $websites = $this->getWebsites();

        /** @var \Magento\Store\Model\Website $website */
        foreach ($websites as $website) {
            $data = array_merge($data, $this->extractWebsiteData($website));
        }

        return [
            (string)__('Websites Tree') => [
                'headers' => [__('ID'), __('Name'), __('Code'), __('Type'), __('Root Category')],
                'data' => $data
            ]
        ];
    }

    /**
     * Extract data from website
     *
     * @param \Magento\Store\Model\Website $website
     * @return array
     */
    protected function extractWebsiteData(Website $website)
    {
        $name = $website->getName() . ($website->getIsDefault() ? ' [*]' : '');
        $data = [
            [$website->getId(), $name, $website->getCode(), 'website', '']
        ];
        $defaultStoreId = $website->getDefaultGroupId();
        $stores = $website->getGroups();

        /** @var \Magento\Store\Model\Group $store */
        foreach ($stores as $store) {
            $data = array_merge($data, $this->extractStoreData($store, $defaultStoreId));
        }
        return $data;
    }

    /**
     * Extract data from store
     *
     * @param \Magento\Store\Model\Group $store
     * @param string $defaultStoreId
     * @return array
     */
    protected function extractStoreData(Store $store, $defaultStoreId)
    {
        $data = [];
        $name = '    ' . $store->getName() . ($defaultStoreId == $store->getId()  ? ' [*]' : '');
        $rootCategoryCollection = $this->getRootCategoryCollection();

        /** @var \Magento\Catalog\Model\Category|null $rootCategory */
        $rootCategory = $rootCategoryCollection->getItemById($store->getRootCategoryId());
        $data[] = [
            $store->getId(),
            $name,
            '',
            'store',
            $rootCategory ? $rootCategory->getName() : 'n/a'
        ];

        $defaultStoreViewId = $store->getDefaultStoreId();
        $storeViews = $store->getStores();

        /** @var \Magento\Store\Model\Store $storeView */
        foreach ($storeViews as $storeView) {
            $data[] = $this->extractStoreViewData($storeView, $defaultStoreViewId);
        }
        return $data;
    }

    /**
     * Extract data from store view
     *
     * @param \Magento\Store\Model\Store $storeView
     * @param string $defaultStoreViewId
     * @return array
     */
    protected function extractStoreViewData(StoreView $storeView, $defaultStoreViewId)
    {
        $name = '        '
            . (!$storeView->getIsActive() ? '-disabled- ' : '')
            . $storeView->getName()
            . ($defaultStoreViewId == $storeView->getId() ? ' [*]' : '');
        return [
            $storeView->getStoreId(),
            $name,
            $storeView->getCode(),
            'store view',
            ''
        ];
    }
}
