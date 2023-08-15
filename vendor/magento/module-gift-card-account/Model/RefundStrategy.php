<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Model;

use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Contains a refund strategy for Gift Card accounts.
 */
class RefundStrategy
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Checks if Gift Card should be refunded to Store Credit or not.
     *
     * @param CreditmemoInterface $creditmemo
     * @return bool
     */
    public function isRefundToStoreCredit(CreditmemoInterface $creditmemo): bool
    {
        /** @var OrderInterface $order */
        $order = $creditmemo->getOrder();
        if ($order->getCustomerIsGuest()) {
            return false;
        }

        // Gets 'Enable Store Credit Functionality' flag from the Scope Config.
        $customerBalanceIsEnabled = $this->scopeConfig->isSetFlag(
            'customer/magento_customerbalance/is_enabled',
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        );

        return $customerBalanceIsEnabled;
    }
}
