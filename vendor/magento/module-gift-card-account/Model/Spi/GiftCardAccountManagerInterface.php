<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Spi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;

/**
 * Manage gift card accounts.
 *
 * Additional logic required for this GiftCardAccount module implementation which is not exposed as API.
 *
 * @api
 */
interface GiftCardAccountManagerInterface
{
    /**
     * For requests by code made by users.
     *
     * Has additional validations for user-requested gift card accounts.
     *
     * @param string $code
     * @param int|null $websiteId The account must be originated from given website, null for any.
     * @param float|null $balanceGTE Check that the account has balance over or equal to given value, null for any.
     * @param bool $onlyEnabled True - account must be enabled, false - any.
     * @param bool $notExpired True - account must not be expired, false - any.
     * @return GiftCardAccountInterface
     * @throws NoSuchEntityException Account with such code is not found.
     * @throws \InvalidArgumentException When the account does not fit requirements.
     * @throws TooManyAttemptsException When the were too many user requests.
     */
    public function requestByCode(
        string $code,
        ?int $websiteId = null,
        ?float $balanceGTE = null,
        bool $onlyEnabled = true,
        bool $notExpired = true
    ): GiftCardAccountInterface;
}
