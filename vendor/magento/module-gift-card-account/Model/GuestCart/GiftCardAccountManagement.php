<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Model\GuestCart;

use Magento\GiftCardAccount\Api\GuestGiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Class GiftCardAccountManagement
 */
class GiftCardAccountManagement implements GuestGiftCardAccountManagementInterface
{
    /**
     * @var \Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface
     */
    protected $giftCartAccountManagement;

    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @param \Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface $giftCartAccountManagement
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        \Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface $giftCartAccountManagement,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->giftCartAccountManagement = $giftCartAccountManagement;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function addGiftCard(
        $cartId,
        \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftCardAccountData
    ) {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->giftCartAccountManagement->saveByQuoteId($quoteIdMask->getQuoteId(), $giftCardAccountData);
    }

    /**
     * {@inheritDoc}
     */
    public function checkGiftCard($cartId, $giftCardCode)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->giftCartAccountManagement->checkGiftCard($quoteIdMask->getQuoteId(), $giftCardCode);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteByQuoteId($cartId, $giftCardCode)
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->giftCartAccountManagement->deleteByQuoteId($quoteIdMask->getQuoteId(), $giftCardCode);
    }
}
