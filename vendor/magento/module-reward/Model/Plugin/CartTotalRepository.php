<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\Cart\CartTotalRepository as TotalRepository;
use Magento\Quote\Model\Quote;

class CartTotalRepository
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var TotalsExtensionFactory
     */
    protected $totalsExtensionFactory;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param TotalsExtensionFactory $totalsExtensionFactory
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        TotalsExtensionFactory $totalsExtensionFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->totalsExtensionFactory = $totalsExtensionFactory;
    }

    /**
     * Add info about reward points to extension attributes.
     *
     * @param TotalRepository $subject
     * @param TotalsInterface $totals
     * @param int $cartId
     * @return TotalsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(TotalRepository $subject, TotalsInterface $totals, $cartId)
    {
        /** @var Quote  $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var \Magento\Quote\Api\Data\TotalsExtensionInterface $extensionAttributes */
        $extensionAttributes = $totals->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
        }

        $rewardPointsBalance = is_numeric($quote->getRewardPointsBalance()) ?
            round($quote->getRewardPointsBalance()) : 0;
        $extensionAttributes->setRewardPointsBalance($rewardPointsBalance);
        $extensionAttributes->setRewardCurrencyAmount($quote->getRewardCurrencyAmount());
        $extensionAttributes->setBaseRewardCurrencyAmount($quote->getBaseRewardCurrencyAmount());

        $totals->setExtensionAttributes($extensionAttributes);

        return $totals;
    }
}
