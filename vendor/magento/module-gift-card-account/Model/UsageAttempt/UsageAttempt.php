<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Model\UsageAttempt;

use Magento\GiftCardAccount\Model\Spi\Data\UsageAttemptInterface;

/**
 * @inheritDoc
 */
class UsageAttempt implements UsageAttemptInterface
{
    /**
     * @var int|null
     */
    private $customerId;

    /**
     * @var string
     */
    private $code;

    /**
     * @param string $code
     * @param int|null $customerId
     */
    public function __construct(
        string $code,
        ?int $customerId = null
    ) {
        $this->code = $code;
        $this->customerId = $customerId;
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
