<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Stores;

use Magento\Store\Model\Website;

/**
 * Websites List report section
 */
class WebsitesListSection extends AbstractStoresSection
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
            $data[] = $this->extractWebsiteData($website);
        }

        return [
            (string)__('Websites List') => [
                'headers' => [
                    __('ID'), __('Name'), __('Code'), __('Is Default'),
                    __('Default Store'), __('Default Store View')
                ],
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
        $defaultStore = $website->getDefaultGroup();
        $defaultStoreView = $website->getDefaultStore();
        return [
            $website->getId(),
            $website->getName(),
            $website->getCode(),
            $website->getIsDefault() ? 'Yes' : 'No',
            $defaultStore
                ? $defaultStore->getName() . ' {ID:' . $defaultStore->getId() . '}'
                : 'n/a',
            $defaultStoreView
                ? $defaultStoreView->getName() . ' {ID:' . $defaultStoreView->getId() . '}'
                : 'n/a'
        ];
    }
}
