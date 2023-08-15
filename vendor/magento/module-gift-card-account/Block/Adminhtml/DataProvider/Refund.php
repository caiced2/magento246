<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Block\Adminhtml\DataProvider;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\GiftCardAccount\Model\RefundStrategy;
use Magento\Sales\Api\Data\CreditmemoInterface;

/**
 * Refund
 */
class Refund implements ArgumentInterface
{
    /**
     * @var RefundStrategy
     */
    private $refundStrategy;

    /**
     * @param RefundStrategy $refundStrategy
     */
    public function __construct(RefundStrategy $refundStrategy)
    {
        $this->refundStrategy = $refundStrategy;
    }

    /**
     * Checks if Gift Card can't be refunded to Store Credit.
     *
     * @param CreditmemoInterface $creditmemo
     * @return bool
     */
    public function isRefundToStoreCredit(CreditmemoInterface $creditmemo)
    {
        return $this->refundStrategy->isRefundToStoreCredit($creditmemo);
    }
}
