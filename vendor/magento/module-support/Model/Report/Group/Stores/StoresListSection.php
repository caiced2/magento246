<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Stores;

use Magento\Store\Model\Group as Store;

/**
 * Stores List report section
 */
class StoresListSection extends AbstractStoresSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = [];
        $stores = $this->getStores();

        /** @var \Magento\Store\Model\Group $store */
        foreach ($stores as $store) {
            $data[] = $this->extractStoreData($store);
        }

        return [
            (string)__('Stores List') => [
                'headers' => [__('ID'), __('Name'), __('Root Category'), __('Default Store View')],
                'data' => $data
            ]
        ];
    }

    /**
     * Extract data from store
     *
     * @param \Magento\Store\Model\Group $store
     * @return array
     */
    protected function extractStoreData(Store $store)
    {
        $rootCategoryCollection = $this->getRootCategoryCollection();

        /** @var \Magento\Catalog\Model\Category|null $rootCategory */
        $rootCategory = $rootCategoryCollection->getItemById($store->getRootCategoryId());
        $defaultStoreView = $store->getDefaultStore();
        return [
            $store->getId(),
            $store->getName(),
            ($rootCategory ? $rootCategory->getName() : 'n/a')
                . ' {ID:' . $store->getRootCategoryId() . '}',
            $defaultStoreView
                ? $defaultStoreView->getName() . ' {ID:' . $defaultStoreView->getId() . '}'
                : 'n/a'
        ];
    }
}
