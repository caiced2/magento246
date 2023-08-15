<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;

/**
 * Reward sales quote total model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Reward extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_rewardData = $rewardData;
        $this->_rewardFactory = $rewardFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->setCode('reward');
    }

    /**
     * Collect reward totals
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if (!$this->_rewardData->isEnabledOnFront($quote->getStore()->getWebsiteId())) {
            return $this;
        }

        $total->setRewardPointsBalance(0)->setRewardCurrencyAmount(0)->setBaseRewardCurrencyAmount(0);

        if ($total->getBaseGrandTotal() >= 0 && $quote->getCustomer()->getId() && $quote->getUseRewardPoints()) {
            /* @var $reward \Magento\Reward\Model\Reward */
            $reward = $quote->getRewardInstance();
            if (!$reward || !$reward->getId()) {
                $customer = $quote->getCustomer();
                $reward = $this->_rewardFactory->create()->setCustomer($customer);
                $reward->setCustomerId($quote->getCustomer()->getId());
                $reward->setWebsiteId($quote->getStore()->getWebsiteId());
                $reward->loadByCustomer();
            }
            $pointsLeft = $reward->getPointsBalance() - $quote->getRewardPointsBalance();
            $rewardCurrencyAmountLeft = $this->priceCurrency->convert(
                $reward->getCurrencyAmount(),
                $quote->getStore()
            ) - $quote->getRewardCurrencyAmount();
            $baseRewardCurrencyAmountLeft = $reward->getCurrencyAmount() - $quote->getBaseRewardCurrencyAmount();
            if ($baseRewardCurrencyAmountLeft >= $total->getBaseGrandTotal()) {
                $pointsBalanceUsed = $reward->getPointsEquivalent($total->getBaseGrandTotal());
                $pointsCurrencyAmountUsed = $total->getGrandTotal();
                $basePointsCurrencyAmountUsed = $total->getBaseGrandTotal();

                $total->setGrandTotal(0);
                $total->setBaseGrandTotal(0);
            } else {
                $pointsBalanceUsed = $reward->getPointsEquivalent($baseRewardCurrencyAmountLeft);
                if ($pointsBalanceUsed > $pointsLeft) {
                    $pointsBalanceUsed = $pointsLeft;
                }
                $pointsCurrencyAmountUsed = $rewardCurrencyAmountLeft;
                $basePointsCurrencyAmountUsed = $baseRewardCurrencyAmountLeft;

                $total->setGrandTotal($total->getGrandTotal() - $pointsCurrencyAmountUsed);
                $total->setBaseGrandTotal($total->getBaseGrandTotal() - $basePointsCurrencyAmountUsed);
            }
            $quote->setRewardPointsBalance(round($quote->getRewardPointsBalance() + $pointsBalanceUsed));
            $quote->setRewardCurrencyAmount($quote->getRewardCurrencyAmount() + $pointsCurrencyAmountUsed);
            $quote->setBaseRewardCurrencyAmount($quote->getBaseRewardCurrencyAmount() + $basePointsCurrencyAmountUsed);

            $total->setRewardPointsBalance(round($pointsBalanceUsed));
            $total->setRewardCurrencyAmount($pointsCurrencyAmountUsed);
            $total->setBaseRewardCurrencyAmount($basePointsCurrencyAmountUsed);
        }
        return $this;
    }

    /**
     * Retrieve reward total data and set it to quote address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address|Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        if (!$this->_rewardData->isEnabledOnFront()) {
            return null;
        }
        if ($total->getRewardCurrencyAmount()) {
            return [
                'code' => $this->getCode(),
                'title' => $this->_rewardData->formatReward($total->getRewardPointsBalance()),
                'value' => -$total->getRewardCurrencyAmount(),
            ];
        }
        return null;
    }
}
