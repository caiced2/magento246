<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Plugin;

use Magento\Quote\Api\Data\TotalSegmentExtensionFactory;
use Magento\Quote\Model\Cart\TotalsConverter as QuoteTotalsConverter;
use Magento\Quote\Api\Data\TotalSegmentInterface;
use Magento\Quote\Model\Quote\Address\Total as QuoteAddressTotal;
use Magento\Quote\Api\Data\TotalSegmentExtensionInterface;

/**
 * Plugin for Magento\Quote\Model\Cart\TotalsConverter
 */
class TotalsConverter
{
    /**
     * @var TotalSegmentExtensionFactory
     */
    private $totalSegmentExtensionFactory;

    /**
     * @var string
     */
    private $code;

    /**
     * @param TotalSegmentExtensionFactory $totalSegmentExtensionFactory
     */
    public function __construct(TotalSegmentExtensionFactory $totalSegmentExtensionFactory)
    {
        $this->totalSegmentExtensionFactory = $totalSegmentExtensionFactory;
        $this->code = 'giftwrapping';
    }

    /**
     * Update totals with gift wrapping information
     *
     * @param QuoteTotalsConverter $subject
     * @param TotalSegmentInterface[] $result
     * @param QuoteAddressTotal[] $addressTotals
     * @return TotalSegmentInterface[]
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterProcess(QuoteTotalsConverter $subject, $result, $addressTotals)
    {
        if (empty($addressTotals[$this->code])) {
            return $result;
        }

        $addressTotal = $addressTotals[$this->code];

        /** @var TotalSegmentExtensionInterface $totalSegmentExtension */
        $totalSegmentExtension = $this->totalSegmentExtensionFactory->create();
        $totalSegmentExtension->setGwItemIds($addressTotal->getGwItemIds());
        $totalSegmentExtension->setGwOrderId($addressTotal->getGwId());
        $totalSegmentExtension->setGwPrice($addressTotal->getGwPrice());
        $totalSegmentExtension->setGwBasePrice($addressTotal->getGwBasePrice());
        $totalSegmentExtension->setGwItemsPrice($addressTotal->getGwItemsPrice());
        $totalSegmentExtension->setGwItemsBasePrice($addressTotal->getGwItemsBasePrice());
        $totalSegmentExtension->setGwAllowGiftReceipt($addressTotal->getGwAllowGiftReceipt());
        $totalSegmentExtension->setGwAddCard($addressTotal->getGwAddCard());
        $totalSegmentExtension->setGwCardPrice($addressTotal->getGwCardPrice());
        $totalSegmentExtension->setGwCardBasePrice($addressTotal->getGwCardBasePrice());
        $totalSegmentExtension->setGwTaxAmount($addressTotal->getGwTaxAmount());
        $totalSegmentExtension->setGwBaseTaxAmount($addressTotal->getGwBaseTaxAmount());
        $totalSegmentExtension->setGwItemsTaxAmount($addressTotal->getGwItemsTaxAmount());
        $totalSegmentExtension->setGwItemsBaseTaxAmount($addressTotal->getGwItemsBaseTaxAmount());
        $totalSegmentExtension->setGwCardTaxAmount($addressTotal->getGwCardTaxAmount());
        $totalSegmentExtension->setGwCardBaseTaxAmount($addressTotal->getGwCardBaseTaxAmount());
        $totalSegmentExtension->setGwPriceInclTax($addressTotal->getGwPriceInclTax());
        $totalSegmentExtension->setGwBasePriceInclTax($addressTotal->getGwBasePriceInclTax());
        $totalSegmentExtension->setGwCardPriceInclTax($addressTotal->getGwCardPriceInclTax());
        $totalSegmentExtension->setGwCardBasePriceInclTax($addressTotal->getGwCardBasePriceInclTax());
        $totalSegmentExtension->setGwItemsPriceInclTax($addressTotal->getGwItemsPriceInclTax());
        $totalSegmentExtension->setGwItemsBasePriceInclTax($addressTotal->getGwItemsBasePriceInclTax());
        $result[$this->code]->setExtensionAttributes($totalSegmentExtension);

        return $result;
    }
}
