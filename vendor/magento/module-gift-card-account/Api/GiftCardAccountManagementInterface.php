<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;

/**
 * Interface GiftCardAccountManagementInterface
 * @api
 * @since 100.0.2
 */
interface GiftCardAccountManagementInterface
{
    /**
     * Remove GiftCard Account entity
     *
     * @param int $cartId
     * @param string $giftCardCode
     * @throws CouldNotDeleteException
     *
     * @return bool
     */
    public function deleteByQuoteId($cartId, $giftCardCode);

    /**
     * Return GiftCard Account cards.
     *
     * @param int $quoteId
     *
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface
     */
    public function getListByQuoteId($quoteId);

    /**
     * Add gift card to the cart.
     *
     * @param int $cartId
     * @param GiftCardAccountInterface $giftCardAccountData Composite gift card account.
     * @throws TooManyAttemptsException
     * @throws CouldNotSaveException
     *
     * @return bool
     */
    public function saveByQuoteId(
        $cartId,
        GiftCardAccountInterface $giftCardAccountData
    );

    /**
     * Check gift card balance if applied to given cart.
     *
     * @param int $cartId
     * @param string $giftCardCode
     * @throws NoSuchEntityException
     * @throws TooManyAttemptsException
     *
     * @return float
     */
    public function checkGiftCard($cartId, $giftCardCode);
}
