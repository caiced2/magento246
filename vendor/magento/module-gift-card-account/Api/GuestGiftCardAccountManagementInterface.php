<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Api;

use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;

/**
 * Interface GuestGiftCardAccountManagementInterface
 * @api
 * @since 100.0.2
 */
interface GuestGiftCardAccountManagementInterface
{
    /**
     * Add gift card to the cart.
     *
     * @param string $cartId
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftCardAccountData
     * @throws TooManyAttemptsException
     * @return bool
     */
    public function addGiftCard(
        $cartId,
        \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface $giftCardAccountData
    );

    /**
     * Check gift card balance if added to the cart.
     *
     * @param string $cartId
     * @param string $giftCardCode
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws TooManyAttemptsException
     * @return float
     */
    public function checkGiftCard($cartId, $giftCardCode);

    /**
     * Remove GiftCard Account entity.
     *
     * @param string $cartId
     * @param string $giftCardCode
     * @return bool
     * @since 100.1.0
     */
    public function deleteByQuoteId($cartId, $giftCardCode);
}
