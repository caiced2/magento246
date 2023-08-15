<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;

/**
 * Service responsible for redeeming a gift card.
 *
 * @api
 */
interface GiftCardRedeemerInterface
{
    /**
     * Redeem gift card by code.
     *
     * @param string $code Gift card code.
     * @param int $forCustomerId Redeem for this customer.
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws TooManyAttemptsException
     */
    public function redeem(string $code, int $forCustomerId): void;
}
