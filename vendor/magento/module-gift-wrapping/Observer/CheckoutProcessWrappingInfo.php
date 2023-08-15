<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

use Magento\Framework\Event\ObserverInterface;

class CheckoutProcessWrappingInfo implements ObserverInterface
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
     * Process gift wrapping options on checkout proccess
     *
     * @param \Magento\Framework\DataObject $observer
     * @throws \InvalidArgumentException
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $giftWrappingInfo = $request->getParam('giftwrapping');

        if (!is_array($giftWrappingInfo)) {
            return $this;
        }
        $quote = $observer->getEvent()->getQuote();
        foreach ($giftWrappingInfo as $type => $wrappingEntities) {
            if (!is_array($wrappingEntities)) {
                throw new \InvalidArgumentException('Invalid entity by index ' . $type);
            }
            foreach ($wrappingEntities as $entityId => $data) {
                switch ((string)$type) {
                    case 'quote':
                        $entity = $quote;
                        $this->itemInfoManager->saveOrderInfo($entity, $data);
                        break;
                    case 'quote_item':
                        $entity = $quote->getItemById($entityId);
                        $this->itemInfoManager->saveItemInfo($entity, $data);
                        break;
                    case 'quote_address':
                        $entity = $quote->getAddressById($entityId);
                        $this->itemInfoManager->saveOrderInfo($entity, $data);
                        break;
                    case 'quote_address_item':
                        $giftOptionsInfo = $request->getParam('giftoptions');
                        if (!is_array($giftOptionsInfo) || empty($giftOptionsInfo)) {
                            throw new \InvalidArgumentException('Invalid "giftoptions" parameter');
                        }
                        $entity = $quote->getAddressById(
                            $giftOptionsInfo[$type][$entityId]['address']
                        )->getItemById(
                            $entityId
                        );
                        $this->itemInfoManager->saveItemInfo($entity, $data);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid wrapping type:' . $type);
                }
            }
        }
        return $this;
    }
}
