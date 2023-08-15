<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\GiftRegistry\Helper\Data as Helper;

class GiftRegistryConfigProvider implements ConfigProviderInterface
{
    /**
     * Entity
     *
     * @var EntityFactory
     */
    protected $entityFactory;

    /**
     * Checkout session
     *
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Gift Registry helper
     *
     * @var Session
     */
    protected $giftRegistryHelper;

    /**
     * @param Helper $giftRegistryHelper
     * @param Session $checkoutSession
     * @param EntityFactory $entityFactory
     */
    public function __construct(
        Helper $giftRegistryHelper,
        Session $checkoutSession,
        EntityFactory $entityFactory
    ) {
        $this->giftRegistryHelper = $giftRegistryHelper;
        $this->checkoutSession = $checkoutSession;
        $this->entityFactory = $entityFactory;
    }

    /**
     * Get customer quote gift registry items
     *
     * @return array
     */
    protected function getGiftRegistryQuoteItems()
    {
        $items = [];
        if ($this->checkoutSession->getQuoteId()) {
            $quote = $this->checkoutSession->getQuote();
            $model = $this->entityFactory->create();
            foreach ($quote->getItemsCollection() as $quoteItem) {
                /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
                $item = [];
                if ($registryItemId = $quoteItem->getGiftregistryItemId()) {
                    $model->loadByEntityItem($registryItemId);
                    $item['entity_id'] = $model->getId();
                    $item['item_id'] = $registryItemId;
                    $item['is_address'] = $model->getShippingAddress() ? 1 : 0;
                    $items[$quoteItem->getId()] = $item;
                }
            }
        }
        return $items;
    }

    /**
     * Get quote unique gift registry item for onepage checkout
     *
     * @return false|int
     */
    protected function getItem()
    {
        $items = [];
        foreach ($this->getGiftRegistryQuoteItems() as $registryItem) {
            $items[$registryItem['entity_id']] = $registryItem;
        }
        if (count($items) == 1) {
            $item = array_shift($items);
            if ($item['is_address']) {
                return $item['item_id'];
            }
        }
        return false;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'giftRegistry' => [
                'available' => $this->giftRegistryHelper->isEnabled(),
                'id' => $this->getItem(),
            ],
        ];
    }
}
