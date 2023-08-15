<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Math\FloatComparator;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Prepares credit memo for refund by setting needed data.
 */
class CreditmemoDataImportObserver implements ObserverInterface
{
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    /**
     * @var FloatComparator
     */
    private $floatComparator;

    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param FloatComparator $floatComparator
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        FloatComparator $floatComparator
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->floatComparator = $floatComparator;
    }

    /**
     * Sets refund flag for manual refund (with "Refund to Store Credit" input)
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $input = $observer->getEvent()->getInput();
        $refundCustomerBalanceReturnEnable = !empty($input['refund_customerbalance_return_enable']);
        $refundCustomerBalanceAmount = !empty($input['refund_customerbalance_return'])
            ? $input['refund_customerbalance_return']
            : null;

        if ($refundCustomerBalanceReturnEnable && is_numeric($refundCustomerBalanceAmount)) {
            $refundCustomerBalanceAmount = max(
                0,
                min($creditMemo->getBaseCustomerBalanceReturnMax(), $refundCustomerBalanceAmount)
            );
            $this->prepareCreditMemoForRefund($creditMemo, $refundCustomerBalanceAmount);
        }

        if (!empty($input['refund_customerbalance'])) {
            $creditMemo->setRefundCustomerBalance(true);
        }

        if (!empty($input['refund_real_customerbalance'])) {
            $creditMemo->setRefundRealCustomerBalance(true);
            $creditMemo->setPaymentRefundDisallowed(true);
        }
    }

    /**
     * Sets refund flag to creditmemo based on user input or presents gift card in the order.
     *
     * @param Creditmemo $creditMemo
     * @param float $amount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareCreditMemoForRefund(Creditmemo $creditMemo, $amount)
    {
        if (!$this->validateAmount($amount)) {
            return;
        }

        $baseAmount = $this->priceCurrency->round($amount);
        $convertedAmount = $this->priceCurrency->round(
            $baseAmount * $creditMemo->getOrder()->getBaseToOrderRate()
        );

        $creditMemo->setBsCustomerBalTotalRefunded($baseAmount);
        $creditMemo->setCustomerBalTotalRefunded($convertedAmount);
        $creditMemo->setBaseCustomerBalanceRefunded($baseAmount);
        $creditMemo->setCustomerBalanceRefunded($amount);
        //setting flag to make actual refund to customer balance after creditmemo save
        $creditMemo->setCustomerBalanceRefundFlag(true);
        //allow online refund
        $creditMemo->setPaymentRefundDisallowed(false);

        if ($this->floatComparator->greaterThanOrEqual($creditMemo->getBaseGrandTotal(), $baseAmount)) {
            $this->updateCreditMemoTotals($creditMemo, $baseAmount, $convertedAmount);
        }
    }

    /**
     * Updates credit memo totals. If base total is zero, sets credit memo as zero allowed.
     *
     * @param Creditmemo $creditMemo
     * @param float $baseAmount
     * @param float $convertedAmount
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function updateCreditMemoTotals(Creditmemo $creditMemo, float $baseAmount, float $convertedAmount)
    {
        $creditMemo->setBaseCustomerBalanceAmount($creditMemo->getBaseCustomerBalanceAmount() + $baseAmount);
        $creditMemo->setCustomerBalanceAmount($creditMemo->getCustomerBalanceAmount() + $convertedAmount);
        $creditMemo->setBaseCustomerBalanceReturnMax(max($baseAmount, $creditMemo->getBaseCustomerBalanceReturnMax()));
        $creditMemo->setCustomerBalanceReturnMax(max($convertedAmount, $creditMemo->getCustomerBalanceReturnMax()));
        if ($this->floatComparator->equal($creditMemo->getBaseGrandTotal(), 0)) {
            $creditMemo->setAllowZeroGrandTotal(true);
        }
    }

    /**
     * Validates amount for refund.
     *
     * @param float $amount
     * @return bool
     */
    private function validateAmount($amount)
    {
        return is_numeric($amount) && ($amount > 0);
    }
}
