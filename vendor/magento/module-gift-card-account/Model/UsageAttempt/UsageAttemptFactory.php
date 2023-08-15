<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\UsageAttempt;

use Magento\GiftCardAccount\Model\Spi\Data\UsageAttemptInterface;
use Magento\GiftCardAccount\Model\Spi\UsageAttemptFactoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

/**
 * @inheritDoc
 */
class UsageAttemptFactory implements UsageAttemptFactoryInterface
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @param CustomerSession $customerSession
     */
    public function __construct(CustomerSession $customerSession)
    {
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritDoc
     */
    public function create(string $code): UsageAttemptInterface
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {
            $customerId = (int)$customerId;
        }

        return new UsageAttempt($code, $customerId);
    }
}
