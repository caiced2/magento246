<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Quote\Api\CartRepositoryInterface as QuoteRepositoryInterface;
use Magento\GiftWrapping\Model\WrappingFactory;
use Magento\GiftWrapping\Helper\Data as DataHelper;
use Magento\GiftMessage\Api\CartRepositoryInterface;
use Magento\GiftMessage\Api\Data\MessageInterface;
use Magento\GiftMessage\Api\Data\MessageExtensionInterface;

/**
 * Plugin for Magento\GiftMessage\Api\CartRepositoryInterface
 */
class MessageCartRepository
{
    /**
     * @var QuoteRepositoryInterface
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
     * @param QuoteRepositoryInterface $quoteRepository
     * @param WrappingFactory $wrappingFactory
     * @param DataHelper $helper
     */
    public function __construct(
        QuoteRepositoryInterface $quoteRepository,
        WrappingFactory $wrappingFactory,
        DataHelper $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->wrappingFactory = $wrappingFactory;
        $this->dataHelper = $helper;
    }

    /**
     * Set gift wrapping from message for cart
     *
     * @param CartRepositoryInterface $subject
     * @param bool $result
     * @param int $cartId
     * @param MessageInterface $giftMessage
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(CartRepositoryInterface $subject, $result, $cartId, MessageInterface $giftMessage)
    {
        $wrappingInfo = [];
        $quote = $this->quoteRepository->getActive($cartId);
        $extensionAttributes = $giftMessage->getExtensionAttributes();

        if ($extensionAttributes) {
            $wrappingInfo = $this->updateWrappingInfoWithId($wrappingInfo, $extensionAttributes);
            $wrappingInfo = $this->updateWrappingInfoWithAllowGiftReceipt($wrappingInfo, $extensionAttributes);
            $wrappingInfo = $this->updateWrappingInfoWithAddPrintedCard($wrappingInfo, $extensionAttributes);
        }

        if ($wrappingInfo) {
            if ($quote->getShippingAddress()) {
                $quote->getShippingAddress()->addData($wrappingInfo);
            }

            $quote->addData($wrappingInfo)->save();
        }

        return true;
    }

    /**
     * Update wrapping info with id
     *
     * @param array $wrappingInfo
     * @param MessageExtensionInterface $extensionAttributes
     * @return array
     */
    private function updateWrappingInfoWithId(array $wrappingInfo, MessageExtensionInterface $extensionAttributes)
    {
        if ($this->dataHelper->isGiftWrappingAvailableForOrder()) {
            $wrappingInfo['gw_id'] = $this->wrappingFactory->create()
                ->load($extensionAttributes->getWrappingId())
                ->getId();
        }

        return $wrappingInfo;
    }

    /**
     * Update wrapping info with information whether gift receipt is allowed
     *
     * @param array $wrappingInfo
     * @param MessageExtensionInterface $extensionAttributes
     * @return array
     */
    private function updateWrappingInfoWithAllowGiftReceipt(
        array $wrappingInfo,
        MessageExtensionInterface $extensionAttributes
    ) {
        if ($this->dataHelper->allowGiftReceipt()) {
            $allowGiftReceipt = $extensionAttributes->getWrappingAllowGiftReceipt();

            if ($allowGiftReceipt !== null) {
                $wrappingInfo['gw_allow_gift_receipt'] = $allowGiftReceipt;
            }
        }

        return $wrappingInfo;
    }

    /**
     * Update wrapping info with information whether to add printed card
     *
     * @param array $wrappingInfo
     * @param MessageExtensionInterface $extensionAttributes
     * @return array
     */
    private function updateWrappingInfoWithAddPrintedCard(
        array $wrappingInfo,
        MessageExtensionInterface $extensionAttributes
    ) {
        if ($this->dataHelper->allowPrintedCard()) {
            $addPrintedCard = $extensionAttributes->getWrappingAddPrintedCard();

            if ($addPrintedCard !== null) {
                $wrappingInfo['gw_add_card'] = $addPrintedCard;
            }
        }

        return $wrappingInfo;
    }
}
