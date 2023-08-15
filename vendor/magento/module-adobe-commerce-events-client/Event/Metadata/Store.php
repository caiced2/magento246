<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Metadata;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Returns info about store.
 */
class Store implements EventMetadataInterface
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * Returns info about store, website and store group.
     *
     * @return array
     */
    public function get(): array
    {
        $metadata = [];

        try {
            $store = $this->storeManager->getStore();
            $metadata['storeId'] = $store->getId();
            $metadata['websiteId'] = $store->getWebsiteId();
            $metadata['storeGroupId'] = $store->getStoreGroupId();
        } catch (NoSuchEntityException $exception) {
            $metadata['storeId'] = $metadata['websiteId'] = $metadata['storeGroupId'] = '';
        }

        return $metadata;
    }
}
