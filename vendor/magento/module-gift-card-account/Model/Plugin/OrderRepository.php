<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\GiftCardAccount\Model\GiftCard;
use Magento\GiftCardAccount\Model\GiftCardFactory;
use Magento\GiftCardAccount\Model\Giftcardaccount;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ObjectManager;

/**
 * Plugin for Order repository.
 */
class OrderRepository
{
    /**
     * @var \Magento\Sales\Api\Data\OrderExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var GiftCardFactory
     */
    private $giftCardFactory;

    /**
     * Instance of serializer.
     *
     * @var Json
     */
    private $serializer;

    /**
     * Init Plugin
     * @param \Magento\Sales\Api\Data\OrderExtensionFactory $extensionFactory
     * @param GiftCardFactory $giftCardFactory
     * @param Json|null $serializer
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderExtensionFactory $extensionFactory,
        GiftCardFactory $giftCardFactory,
        Json $serializer = null
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->giftCardFactory = $giftCardFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderInterface $entity
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderInterface $entity
    ) {
        if (!$entity->getGiftCards()) {
            return $entity;
        }
        /** @var \Magento\Sales\Api\Data\OrderExtension $extensionAttributes */
        $extensionAttributes = $entity->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionFactory->create();
        }

        $giftCards = $this->createGiftCards($this->serializer->unserialize($entity->getGiftCards()));

        $extensionAttributes->setGiftCards($giftCards);
        $extensionAttributes->setBaseGiftCardsAmount($entity->getBaseGiftCardsAmount());
        $extensionAttributes->setGiftCardsAmount($entity->getGiftCardsAmount());
        $extensionAttributes->setBaseGiftCardsInvoiced($entity->getBaseGiftCardsInvoiced());
        $extensionAttributes->setGiftCardsInvoiced($entity->getGiftCardsInvoiced());
        $extensionAttributes->setBaseGiftCardsRefunded($entity->getBaseGiftCardsRefunded());
        $extensionAttributes->setGiftCardsRefunded($entity->getGiftCardsRefunded());

        $entity->setExtensionAttributes($extensionAttributes);

        return $entity;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $entities
     *
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function afterGetList(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Magento\Sales\Api\Data\OrderSearchResultInterface $entities
    ) {
        /** @var \Magento\Sales\Api\Data\OrderInterface $entity */
        foreach ($entities->getItems() as $entity) {
            $this->afterGet($subject, $entity);
        }

        return $entities;
    }

    /**
     * Create Gift Cards Data Objects
     *
     * @param array $items
     * @return array
     */
    private function createGiftCards(array $items)
    {
        $giftCards = [];
        foreach ($items as $item) {
            /** @var GiftCard $giftCard */
            $giftCard = $this->giftCardFactory->create();
            $giftCard->setId($item[Giftcardaccount::ID]);
            $giftCard->setCode($item[Giftcardaccount::CODE]);
            $giftCard->setAmount($item[Giftcardaccount::AMOUNT]);
            $giftCard->setBaseAmount($item[Giftcardaccount::BASE_AMOUNT]);
            $giftCards[] = $giftCard;
        }
        return $giftCards;
    }
}
