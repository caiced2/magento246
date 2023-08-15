<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Stores;

use Magento\Store\Model\Store as StoreView;

/**
 * Store Views List report section
 */
class StoreViewsListSection extends AbstractStoresSection
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        $data = [];
        $storeViews = $this->getStoreViews();

        /** @var \Magento\Store\Model\Store $storeView */
        foreach ($storeViews as $storeView) {
            $data[] = $this->extractStoreViewData($storeView);
        }

        return [
            (string)__('Store Views List') => [
                'headers' => [__('ID'), __('Name'), __('Code'), __('Enabled'), __('Store')],
                'data' => $data
            ]
        ];
    }

    /**
     * Extract data from store view
     *
     * @param \Magento\Store\Model\Store $storeView
     * @return array
     */
    protected function extractStoreViewData(StoreView $storeView)
    {
        $defaultStore = $storeView->getGroup();
        return [
            $storeView->getId(),
            $storeView->getName(),
            $storeView->getCode(),
            $storeView->getIsActive() ? 'Yes' : 'No',
            $defaultStore
                ? $defaultStore->getName() . ' {ID:' . $defaultStore->getId() . '}'
                : 'n/a'
        ];
    }
}
