<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesRuleStaging\Model\Staging\PreviewStoreIdResolver;
use Magento\SalesRuleStaging\Model\ResourceModel\Rule\AddWebsiteIdsToCollection;

/**
 * Add store id to sales rules upcoming search result
 */
class AddStoreIdToSalesRuleUpcomingSearchResult implements ObserverInterface
{
    /**
     * @var AddWebsiteIdsToCollection
     */
    private $addWebsiteIdsToCollection;

    /**
     * @var PreviewStoreIdResolver
     */
    private $storeIdResolver;

    /**
     * @param AddWebsiteIdsToCollection $addWebsiteIdsToCollection
     * @param PreviewStoreIdResolver $storeIdResolver
     */
    public function __construct(
        AddWebsiteIdsToCollection $addWebsiteIdsToCollection,
        PreviewStoreIdResolver $storeIdResolver
    ) {
        $this->addWebsiteIdsToCollection = $addWebsiteIdsToCollection;
        $this->storeIdResolver = $storeIdResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Staging\Model\Entity\Upcoming\SearchResult $collection */
        $collection = $observer->getCollection();
        if ($collection->getItems()) {
            $this->addWebsiteIdsToCollection->execute($collection);
            foreach ($collection->getItems() as $item) {
                $item->setStoreId($this->storeIdResolver->execute($item->getWebsiteIds() ?? []));
            }
        }
    }
}
