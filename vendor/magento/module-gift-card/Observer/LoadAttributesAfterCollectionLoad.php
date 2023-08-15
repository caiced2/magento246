<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\GiftCard\Model\Product\ReadHandler;

/**
 * Class LoadAttributesAfterCollectionLoad
 */
class LoadAttributesAfterCollectionLoad implements ObserverInterface
{
    /**
     * @var ReadHandler
     */
    private $readHandler;

    /**
     * LoadAttributesAfterCollectionLoad constructor.
     *
     * @param ReadHandler $readHandler
     */
    public function __construct(ReadHandler $readHandler)
    {
        $this->readHandler = $readHandler;
    }

    /**
     * Process `giftcard_amounts` attribute afterLoad logic on loading by collection
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();

        foreach ($collection as $item) {
            if (\Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD == $item->getTypeId()) {
                $attribute = $item->getResource()->getAttribute('giftcard_amounts');
                if ($attribute->getId()) {
                    $this->readHandler->execute($item);
                }
            }
        }
        return $this;
    }
}
