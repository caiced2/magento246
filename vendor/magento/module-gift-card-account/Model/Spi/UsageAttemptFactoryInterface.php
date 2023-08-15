<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\Spi;

use Magento\GiftCardAccount\Model\Spi\Data\UsageAttemptInterface;

/**
 * Initiate an attempt's data.
 *
 * @api
 */
interface UsageAttemptFactoryInterface
{
    /**
     * Create code usage attempt.
     *
     * @param string $code Code requested.
     * @return UsageAttemptInterface
     */
    public function create(string $code): UsageAttemptInterface;
}
