<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class ProcessOrderCreationData implements ObserverInterface
{
    /**
     * @var ItemInfoManager
     */
    protected $itemInfoManager;

    /**
     * @param ItemInfoManager $itemInfoManager
     */
    public function __construct(ItemInfoManager $itemInfoManager)
    {
        $this->itemInfoManager = $itemInfoManager;
    }

    /**
     * Process admin order creation
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getOrderCreateModel()->getQuote();
        $request = $observer->getEvent()->getRequest();
        if (isset($request['giftwrapping'])) {
            foreach ($request['giftwrapping'] as $entityType => $entityData) {
                foreach ($entityData as $entityId => $data) {
                    switch ($entityType) {
                        case 'quote':
                            $entity = $quote;
                            $this->itemInfoManager->saveOrderInfo($entity, $data);
                            break;
                        case 'quote_item':
                            $entity = $quote->getItemById($entityId);
                            $this->itemInfoManager->saveItemInfo($entity, $data);
                            break;
                    }
                }
            }
        }
    }
}
