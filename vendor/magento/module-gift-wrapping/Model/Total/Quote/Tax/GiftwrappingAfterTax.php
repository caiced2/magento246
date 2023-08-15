<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Total\Quote\Tax;

use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\GiftWrapping\Model\Total\Quote\Tax\Giftwrapping;

/**
 * GiftWrapping tax total calculator for quote
 */
class GiftwrappingAfterTax extends AbstractTotal
{
    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $giftWrappingData;

    /**
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     */
    public function __construct(
        \Magento\GiftWrapping\Helper\Data $giftWrappingData
    ) {
        $this->giftWrappingData = $giftWrappingData;
        $this->setCode('tax_giftwrapping');
    }

    /**
     * Collect gift wrapping tax totals
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Address\Total $total
    ) {
        if ($shippingAssignment->getShipping()->getAddress()->getAddressType() !== Address::TYPE_SHIPPING) {
            return $this;
        }

        $extraTaxableDetails = $total->getExtraTaxableDetails();
        if (!$extraTaxableDetails) {
            $extraTaxableDetails = [];
        }

        $this->processWrappingForItems(
            $total,
            $this->getItemTaxDetails($extraTaxableDetails, Giftwrapping::ITEM_TYPE)
        );
        $this->processWrappingForQuote(
            $total,
            $this->getItemTaxDetails($extraTaxableDetails, Giftwrapping::QUOTE_TYPE)
        );
        $this->processPrintedCard(
            $total,
            $this->getItemTaxDetails($extraTaxableDetails, Giftwrapping::PRINTED_CARD_TYPE)
        );
        return $this;
    }

    /**
     * Update wrapping tax total for items
     *
     * @param Address\Total $total
     * @param array $itemTaxDetails
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function processWrappingForItems($total, $itemTaxDetails)
    {
        $gwItemCodeToItemMapping = $total->getGwItemCodeToItemMapping();
        $wrappingForItemsBaseTaxAmount = null;
        $wrappingForItemsTaxAmount = null;
        $wrappingForItemsInclTax = null;
        $baseWrappingForItemsInclTax = null;

        if (!empty($itemTaxDetails)) {
            foreach ($itemTaxDetails as $itemCode => $itemTaxDetail) {
                $itemTaxDetailCount = is_array($itemTaxDetails) ? count($itemTaxDetail) : 0;
                if ($itemTaxDetailCount < 1) {
                    continue;
                }

                // order may have multiple giftwrapping items
                for ($i = 0; $i < $itemTaxDetailCount; $i++) {
                    $gwTaxDetail = $itemTaxDetail[$i];
                    $gwItemCode = $gwTaxDetail['code'];

                    if (!array_key_exists($gwItemCode, $gwItemCodeToItemMapping)) {
                        continue;
                    }
                    $item = $gwItemCodeToItemMapping[$gwItemCode];

                    // search for the right giftwrapping item associated with the address
                    if ($item != null) {
                        break;
                    }
                }

                $wrappingBaseTaxAmount = $gwTaxDetail['base_row_tax'];
                $wrappingTaxAmount = $gwTaxDetail['row_tax'];
                $wrappingForItemsInclTax += $gwTaxDetail['price_incl_tax'] * $item->getQty();
                $baseWrappingForItemsInclTax += $gwTaxDetail['base_price_incl_tax'] * $item->getQty();

                $item->setGwBaseTaxAmount($wrappingBaseTaxAmount / $item->getQty());
                $item->setGwTaxAmount($wrappingTaxAmount / $item->getQty());

                $wrappingForItemsBaseTaxAmount += $wrappingBaseTaxAmount;
                $wrappingForItemsTaxAmount += $wrappingTaxAmount;
            }
        }

        $total->setGwItemsBaseTaxAmount($wrappingForItemsBaseTaxAmount);
        $total->setGwItemsTaxAmount($wrappingForItemsTaxAmount);
        $total->setGwItemsPriceInclTax($wrappingForItemsInclTax);
        $total->setGwItemsBasePriceInclTax($baseWrappingForItemsInclTax);
        return $this;
    }

    /**
     * Collect wrapping tax total for quote
     *
     * @param Address\Total $total
     * @param array $itemTaxDetails
     * @return $this
     */
    protected function processWrappingForQuote($total, $itemTaxDetails)
    {
        $wrappingPriceInclTax = null;
        $wrappingBasePriceInclTax = null;
        $wrappingBaseTaxAmount = null;
        $wrappingTaxAmount = null;

        if (!empty($itemTaxDetails)) {
            //there is only one gift wrapping per quote
            $gwTaxDetail = $itemTaxDetails[CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE][0];
            if (!empty($gwTaxDetail)) {
                $wrappingBaseTaxAmount = $gwTaxDetail['base_row_tax'];
                $wrappingTaxAmount = $gwTaxDetail['row_tax'];
                $wrappingPriceInclTax = $gwTaxDetail['price_incl_tax'];
                $wrappingBasePriceInclTax = $gwTaxDetail['base_price_incl_tax'];
            }
        }

        $total->setGwPriceInclTax($wrappingPriceInclTax);
        $total->setGwBasePriceInclTax($wrappingBasePriceInclTax);
        $total->setGwBaseTaxAmount($wrappingBaseTaxAmount);
        $total->setGwTaxAmount($wrappingTaxAmount);
        return $this;
    }

    /**
     * Collect printed card tax total for quote
     *
     * @param Address\Total $total
     * @param array $itemTaxDetails
     * @return $this
     */
    protected function processPrintedCard($total, $itemTaxDetails)
    {
        $printedCardPriceInclTax = null;
        $printedCardBasePriceInclTax = null;
        $printedCardBaseTaxAmount = null;
        $printedCardTaxAmount = null;

        if (!empty($itemTaxDetails)) {
            //there is only one printed card per quote
            $taxDetail = $itemTaxDetails[CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE][0];
            if (!empty($taxDetail)) {
                $printedCardBaseTaxAmount = $taxDetail['base_row_tax'];
                $printedCardTaxAmount = $taxDetail['row_tax'];
                $printedCardPriceInclTax = $taxDetail['price_incl_tax'];
                $printedCardBasePriceInclTax = $taxDetail['base_price_incl_tax'];
            }
        }

        $total->setGwCardPriceInclTax($printedCardPriceInclTax);
        $total->setGwCardBasePriceInclTax($printedCardBasePriceInclTax);
        $total->setGwCardBaseTaxAmount($printedCardBaseTaxAmount);
        $total->setGwCardTaxAmount($printedCardTaxAmount);
        return $this;
    }

    /**
     * Get Tax details by GiftWrapping Item Type
     *
     * @param array $extraTaxableDetails
     * @param string $itemType
     * @return array
     */
    private function getItemTaxDetails(array $extraTaxableDetails, $itemType)
    {
        return isset($extraTaxableDetails[$itemType]) ? $extraTaxableDetails[$itemType] : [];
    }

    /**
     * Assign wrapping tax totals and labels to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address\Total $total
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return [
            'code' => 'giftwrapping',
            'title' => __('Gift Wrapping'),
            'gw_price' => $total->getGwPrice(),
            'gw_base_price' => $total->getGwBasePrice(),
            'gw_items_price' => $total->getGwItemsPrice(),
            'gw_items_base_price' => $total->getGwItemsBasePrice(),
            'gw_card_price' => $total->getGwCardPrice(),
            'gw_card_base_price' => $total->getGwCardBasePrice(),
            'gw_price_incl_tax' => $total->getGwPriceInclTax(),
            'gw_base_price_incl_tax' => $total->getGwBasePriceInclTax(),
            'gw_card_price_incl_tax' => $total->getGwCardPriceInclTax(),
            'gw_card_base_price_incl_tax' => $total->getGwCardBasePriceInclTax(),
            'gw_tax_amount' => $total->getGwTaxAmount(),
            'gw_base_tax_amount' => $total->getGwBaseTaxAmount(),
            'gw_items_tax_amount' => $total->getGwItemsTaxAmount(),
            'gw_items_base_tax_amount' => $total->getGwItemsBaseTaxAmount(),
            'gw_items_price_incl_tax' => $total->getGwItemsPriceInclTax(),
            'gw_items_base_price_incl_tax' => $total->getGwItemsBasePriceInclTax(),
            'gw_card_tax_amount' => $total->getGwCardTaxAmount(),
            'gw_card_base_tax_amount' => $total->getGwCardBaseTaxAmount(),
        ];
    }
}
