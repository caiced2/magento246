<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Total\Quote;

use Magento\GiftCardAccount\Model\Giftcardaccount as ModelGiftcardaccount;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;

class Giftcardaccount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $_giftCardAccountData = null;

    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $_giftCAFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->_giftCAFactory = $giftCAFactory;
        $this->_giftCardAccountData = $giftCardAccountData;
        $this->priceCurrency = $priceCurrency;
        $this->setCode('giftcardaccount');
    }

    /**
     * Collect giftcertificate totals for specified address
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Quote\Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $baseAmountLeft = $quote->getBaseGiftCardsAmount() - $quote->getBaseGiftCardsAmountUsed();
        $amountLeft = $quote->getGiftCardsAmount() - $quote->getGiftCardsAmountUsed();

        if ($baseAmountLeft >= $total->getBaseGrandTotal()) {
            $baseUsed = $total->getBaseGrandTotal();
            $used = $total->getGrandTotal();

            $total->setBaseGrandTotal(0);
            $total->setGrandTotal(0);
        } else {
            $baseUsed = $baseAmountLeft;
            $used = $amountLeft;

            $total->setBaseGrandTotal($total->getBaseGrandTotal() - $baseAmountLeft);
            $total->setGrandTotal($total->getGrandTotal() - $amountLeft);
        }

        $addressCards = [];
        $usedAddressCards = [];
        if ($baseUsed) {
            $quoteCards = $this->_sortGiftCards($this->_giftCardAccountData->getCards($quote));
            $skipped = 0;
            $baseSaved = 0;
            $saved = 0;
            foreach ($quoteCards as $quoteCard) {
                $card = $quoteCard;
                if ($quoteCard[ModelGiftcardaccount::BASE_AMOUNT] + $skipped <=
                    $quote->getBaseGiftCardsAmountUsed()
                ) {
                    $baseThisCardUsedAmount = $thisCardUsedAmount = 0;
                } elseif ($quoteCard[ModelGiftcardaccount::BASE_AMOUNT] + $baseSaved >
                    $baseUsed
                ) {
                    $baseThisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::BASE_AMOUNT],
                        $baseUsed - $baseSaved
                    );
                    $thisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::AMOUNT],
                        $used - $saved
                    );

                    $baseSaved += $baseThisCardUsedAmount;
                    $saved += $thisCardUsedAmount;
                } elseif ($quoteCard[ModelGiftcardaccount::BASE_AMOUNT] + $skipped + $baseSaved >
                    $quote->getBaseGiftCardsAmountUsed()
                ) {
                    $baseThisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::BASE_AMOUNT],
                        $baseUsed
                    );
                    $thisCardUsedAmount = min(
                        $quoteCard[ModelGiftcardaccount::AMOUNT],
                        $used
                    );

                    $baseSaved += $baseThisCardUsedAmount;
                    $saved += $thisCardUsedAmount;
                } else {
                    $baseThisCardUsedAmount = $thisCardUsedAmount = 0;
                }
                // avoid possible errors in future comparisons
                $card[ModelGiftcardaccount::BASE_AMOUNT] = round($baseThisCardUsedAmount, 4);
                $card[ModelGiftcardaccount::AMOUNT] = round($thisCardUsedAmount, 4);
                $addressCards[] = $card;
                if ($baseThisCardUsedAmount) {
                    $usedAddressCards[] = $card;
                }

                $skipped += $quoteCard[ModelGiftcardaccount::BASE_AMOUNT];
            }
        }
        $this->_giftCardAccountData->setCards($total, $usedAddressCards);
        $total->setUsedGiftCards($total->getGiftCards());
        $this->_giftCardAccountData->setCards($total, $addressCards);

        $baseTotalUsed = $quote->getBaseGiftCardsAmountUsed() + $baseUsed;
        $totalUsed = $quote->getGiftCardsAmountUsed() + $used;

        $quote->setBaseGiftCardsAmountUsed($baseTotalUsed);
        $quote->setGiftCardsAmountUsed($totalUsed);

        $total->setBaseGiftCardsAmount($baseUsed);
        $total->setGiftCardsAmount($used);

        return $this;
    }

    /**
     * Return shopping cart total row items
     *
     * @param Quote $quote
     * @param Quote\Address\Total $total
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $giftCards = $this->_giftCardAccountData->getCards($total);
        if (!empty($giftCards)) {
            return [
                'code' => $this->getCode(),
                'title' => __('Gift Cards'),
                'value' => -$total->getGiftCardsAmount(),
                'gift_cards' => $giftCards
            ];
        }

        return null;
    }

    /**
     * @param array $in
     * @return mixed
     */
    protected function _sortGiftCards($in)
    {
        usort($in, [$this, 'compareGiftCards']);
        return $in;
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function compareGiftCards($a, $b)
    {
        if ($a[ModelGiftcardaccount::BASE_AMOUNT] == $b[ModelGiftcardaccount::BASE_AMOUNT]) {
            return 0;
        }
        return $a[ModelGiftcardaccount::BASE_AMOUNT] > $b[ModelGiftcardaccount::BASE_AMOUNT] ? 1 : -1;
    }
}
