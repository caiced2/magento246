<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Spi\Data;

/**
 * An attempt to use a gift card code.
 *
 * @api
 */
interface UsageAttemptInterface
{
    /**
     * If attempt is initiated by a registered customer then it will return their ID.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Gift card code requested.
     *
     * @return string
     */
    public function getCode(): string;
}
