<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftCardAccount\Observer;

use Magento\Framework\Event\ObserverInterface;

class SalesOrderCancelAfter implements ObserverInterface
{
    /**
     * @var ReturnFundsToStoreCredit
     */
    private $returnFundsToStoreCredit;

    /**
     * @var RevertGiftCardAccountBalance
     */
    private $revertGiftCardAccountBalance;

    /**
     * @param ReturnFundsToStoreCredit $returnFundsToStoreCredit
     * @param RevertGiftCardAccountBalance $revertGiftCardAccountBalance
     */
    public function __construct(
        ReturnFundsToStoreCredit $returnFundsToStoreCredit,
        RevertGiftCardAccountBalance $revertGiftCardAccountBalance
    ) {
        $this->returnFundsToStoreCredit = $returnFundsToStoreCredit;
        $this->revertGiftCardAccountBalance = $revertGiftCardAccountBalance;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer) : void
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getCustomerId()) {
            $this->returnFundsToStoreCredit->execute($observer);
        } else {
            $this->revertGiftCardAccountBalance->execute($observer);
        }
    }
}
