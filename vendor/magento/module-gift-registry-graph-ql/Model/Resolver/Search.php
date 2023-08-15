<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistryGraphQl\Model\Resolver;

use Magento\GiftRegistry\Model\ResourceModel\Entity\CollectionFactory as GiftRegistryCollectionFactory;
use Magento\GiftRegistry\Model\Search\Results\FilterInputs;

class Search
{
    public const SEARCH_EMAIL = 'email';

    public const SEARCH_ID = 'id';

    public const SEARCH_TYPE = 'type';

    /**
     * @var FilterInputs
     */
    private $filterInputs;

    /**
     * @var GiftRegistryCollectionFactory
     */
    private $giftRegistryCollectionFactory;

    /**
     * @param FilterInputs $filterInputs
     * @param GiftRegistryCollectionFactory $giftRegistryCollectionFactory
     */
    public function __construct(
        FilterInputs $filterInputs,
        GiftRegistryCollectionFactory $giftRegistryCollectionFactory
    ) {
        $this->filterInputs = $filterInputs;
        $this->giftRegistryCollectionFactory = $giftRegistryCollectionFactory;
    }

    /**
     * Gets a list of Gift Registries based on search filter parameters
     *
     * @param array $params
     * @return array
     */
    public function search(array $params): array
    {
        $giftRegistryCollection = $this->giftRegistryCollectionFactory->create();
        $giftRegistries = $giftRegistryCollection->applySearchFilters(
            $this->filterInputs->filterInputParams($params, null)
        );

        $items = [];
        foreach ($giftRegistries as $giftRegistry) {
            $items[] = [
                'gift_registry_uid' => $giftRegistry->getUrlKey(),
                'name' => $giftRegistry->getRegistrant(),
                'event_title' => $giftRegistry->getTitle(),
                'type' => $giftRegistry->getType(),
                'location' => $giftRegistry->getEventLocation(),
                'event_date' => $giftRegistry->getEventDate()
            ];
        }

        return $items;
    }
}
