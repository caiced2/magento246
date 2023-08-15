<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Observer;

class ItemInfoManager
{
    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $wrappingFactory;

    /**
     * @param \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
     */
    public function __construct(
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
    ) {
        $this->wrappingFactory = $wrappingFactory;
    }

    /**
     * Prepare entire order info about gift wrapping
     *
     * @param mixed $entity
     * @param array $data
     * @return $this
     */
    public function saveOrderInfo($entity, $data)
    {
        if (is_array($data)) {
            $wrappingInfo = [];
            if (isset($data['design'])) {
                $wrapping = $this->wrappingFactory->create()->load($data['design']);
                $wrappingInfo['gw_id'] = $wrapping->getId();
            }
            $wrappingInfo['gw_allow_gift_receipt'] = isset($data['allow_gift_receipt']);
            $wrappingInfo['gw_add_card'] = isset($data['add_printed_card']);
            if ($entity->getShippingAddress()) {
                $entity->getShippingAddress()->addData($wrappingInfo);
            }
            $entity->addData($wrappingInfo)->save();
        }
        return $this;
    }

    /**
     * Prepare quote item info about gift wrapping
     *
     * @param mixed $entity
     * @param array $data
     * @return $this
     */
    public function saveItemInfo($entity, $data)
    {
        if (is_array($data) && isset($data['design'])) {
            $wrapping = $this->wrappingFactory->create()->load($data['design']);
            $entity->setGwId($wrapping->getId())->save();
        }
        return $this;
    }
}
