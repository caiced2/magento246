<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Plugin;

use Magento\GiftMessage\Api\Data\MessageInterface;
use Magento\GiftMessage\Api\ItemRepositoryInterface;
use Magento\GiftWrapping\Helper\Data as DataHelper;
use Magento\GiftWrapping\Model\WrappingFactory;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Plugin for Magento\GiftMessage\Api\ItemRepositoryInterface
 */
class MessageItemRepository
{
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var WrappingFactory
     */
    private $wrappingFactory;

    /**
     * @var DataHelper
     */
    private $dataHelper;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param WrappingFactory $wrappingFactory
     * @param DataHelper $helper
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        WrappingFactory $wrappingFactory,
        DataHelper $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->wrappingFactory = $wrappingFactory;
        $this->dataHelper = $helper;
    }

    /**
     * Set gift wrapping from message for cart item
     *
     * @param ItemRepositoryInterface $subject
     * @param bool $result
     * @param int $cartId
     * @param MessageInterface $giftMessage
     * @param int $itemId
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterSave(
        ItemRepositoryInterface $subject,
        $result,
        $cartId,
        MessageInterface $giftMessage,
        $itemId
    ) {
        $extensionAttributes = $giftMessage->getExtensionAttributes();
        $product = $this->quoteRepository->getActive($cartId)
            ->getItemById($itemId)->getProduct();
        if ($extensionAttributes &&
            (
                $this->dataHelper->isGiftWrappingAvailableForItems() ||
                $product->getGiftWrappingAvailable()
            )
        ) {
            $this->quoteRepository->getActive($cartId)
                ->getItemById($itemId)
                ->setGwId($this->wrappingFactory->create()->load($extensionAttributes->getWrappingId())->getId())
                ->save();
        }

        return true;
    }
}
