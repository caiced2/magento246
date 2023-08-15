<?php
namespace Magento\Sales\Api\Data;

/**
 * Extension class for @see \Magento\Sales\Api\Data\OrderInterface
 */
class OrderExtension extends \Magento\Framework\Api\AbstractSimpleObject implements OrderExtensionInterface
{
    /**
     * @return \Magento\Sales\Api\Data\ShippingAssignmentInterface[]|null
     */
    public function getShippingAssignments()
    {
        return $this->_get('shipping_assignments');
    }

    /**
     * @param \Magento\Sales\Api\Data\ShippingAssignmentInterface[] $shippingAssignments
     * @return $this
     */
    public function setShippingAssignments($shippingAssignments)
    {
        $this->setData('shipping_assignments', $shippingAssignments);
        return $this;
    }

    /**
     * @return \Magento\Payment\Api\Data\PaymentAdditionalInfoInterface[]|null
     */
    public function getPaymentAdditionalInfo()
    {
        return $this->_get('payment_additional_info');
    }

    /**
     * @param \Magento\Payment\Api\Data\PaymentAdditionalInfoInterface[] $paymentAdditionalInfo
     * @return $this
     */
    public function setPaymentAdditionalInfo($paymentAdditionalInfo)
    {
        $this->setData('payment_additional_info', $paymentAdditionalInfo);
        return $this;
    }

    /**
     * @return \Magento\Tax\Api\Data\OrderTaxDetailsAppliedTaxInterface[]|null
     */
    public function getAppliedTaxes()
    {
        return $this->_get('applied_taxes');
    }

    /**
     * @param \Magento\Tax\Api\Data\OrderTaxDetailsAppliedTaxInterface[] $appliedTaxes
     * @return $this
     */
    public function setAppliedTaxes($appliedTaxes)
    {
        $this->setData('applied_taxes', $appliedTaxes);
        return $this;
    }

    /**
     * @return \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface[]|null
     */
    public function getItemAppliedTaxes()
    {
        return $this->_get('item_applied_taxes');
    }

    /**
     * @param \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface[] $itemAppliedTaxes
     * @return $this
     */
    public function setItemAppliedTaxes($itemAppliedTaxes)
    {
        $this->setData('item_applied_taxes', $itemAppliedTaxes);
        return $this;
    }

    /**
     * @return boolean|null
     */
    public function getConvertingFromQuote()
    {
        return $this->_get('converting_from_quote');
    }

    /**
     * @param boolean $convertingFromQuote
     * @return $this
     */
    public function setConvertingFromQuote($convertingFromQuote)
    {
        $this->setData('converting_from_quote', $convertingFromQuote);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceAmount()
    {
        return $this->_get('base_customer_balance_amount');
    }

    /**
     * @param float $baseCustomerBalanceAmount
     * @return $this
     */
    public function setBaseCustomerBalanceAmount($baseCustomerBalanceAmount)
    {
        $this->setData('base_customer_balance_amount', $baseCustomerBalanceAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCustomerBalanceAmount()
    {
        return $this->_get('customer_balance_amount');
    }

    /**
     * @param float $customerBalanceAmount
     * @return $this
     */
    public function setCustomerBalanceAmount($customerBalanceAmount)
    {
        $this->setData('customer_balance_amount', $customerBalanceAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceInvoiced()
    {
        return $this->_get('base_customer_balance_invoiced');
    }

    /**
     * @param float $baseCustomerBalanceInvoiced
     * @return $this
     */
    public function setBaseCustomerBalanceInvoiced($baseCustomerBalanceInvoiced)
    {
        $this->setData('base_customer_balance_invoiced', $baseCustomerBalanceInvoiced);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCustomerBalanceInvoiced()
    {
        return $this->_get('customer_balance_invoiced');
    }

    /**
     * @param float $customerBalanceInvoiced
     * @return $this
     */
    public function setCustomerBalanceInvoiced($customerBalanceInvoiced)
    {
        $this->setData('customer_balance_invoiced', $customerBalanceInvoiced);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceRefunded()
    {
        return $this->_get('base_customer_balance_refunded');
    }

    /**
     * @param float $baseCustomerBalanceRefunded
     * @return $this
     */
    public function setBaseCustomerBalanceRefunded($baseCustomerBalanceRefunded)
    {
        $this->setData('base_customer_balance_refunded', $baseCustomerBalanceRefunded);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCustomerBalanceRefunded()
    {
        return $this->_get('customer_balance_refunded');
    }

    /**
     * @param float $customerBalanceRefunded
     * @return $this
     */
    public function setCustomerBalanceRefunded($customerBalanceRefunded)
    {
        $this->setData('customer_balance_refunded', $customerBalanceRefunded);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceTotalRefunded()
    {
        return $this->_get('base_customer_balance_total_refunded');
    }

    /**
     * @param float $baseCustomerBalanceTotalRefunded
     * @return $this
     */
    public function setBaseCustomerBalanceTotalRefunded($baseCustomerBalanceTotalRefunded)
    {
        $this->setData('base_customer_balance_total_refunded', $baseCustomerBalanceTotalRefunded);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getCustomerBalanceTotalRefunded()
    {
        return $this->_get('customer_balance_total_refunded');
    }

    /**
     * @param float $customerBalanceTotalRefunded
     * @return $this
     */
    public function setCustomerBalanceTotalRefunded($customerBalanceTotalRefunded)
    {
        $this->setData('customer_balance_total_refunded', $customerBalanceTotalRefunded);
        return $this;
    }

    /**
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardInterface[]|null
     */
    public function getGiftCards()
    {
        return $this->_get('gift_cards');
    }

    /**
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardInterface[] $giftCards
     * @return $this
     */
    public function setGiftCards($giftCards)
    {
        $this->setData('gift_cards', $giftCards);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseGiftCardsAmount()
    {
        return $this->_get('base_gift_cards_amount');
    }

    /**
     * @param float $baseGiftCardsAmount
     * @return $this
     */
    public function setBaseGiftCardsAmount($baseGiftCardsAmount)
    {
        $this->setData('base_gift_cards_amount', $baseGiftCardsAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getGiftCardsAmount()
    {
        return $this->_get('gift_cards_amount');
    }

    /**
     * @param float $giftCardsAmount
     * @return $this
     */
    public function setGiftCardsAmount($giftCardsAmount)
    {
        $this->setData('gift_cards_amount', $giftCardsAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseGiftCardsInvoiced()
    {
        return $this->_get('base_gift_cards_invoiced');
    }

    /**
     * @param float $baseGiftCardsInvoiced
     * @return $this
     */
    public function setBaseGiftCardsInvoiced($baseGiftCardsInvoiced)
    {
        $this->setData('base_gift_cards_invoiced', $baseGiftCardsInvoiced);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getGiftCardsInvoiced()
    {
        return $this->_get('gift_cards_invoiced');
    }

    /**
     * @param float $giftCardsInvoiced
     * @return $this
     */
    public function setGiftCardsInvoiced($giftCardsInvoiced)
    {
        $this->setData('gift_cards_invoiced', $giftCardsInvoiced);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseGiftCardsRefunded()
    {
        return $this->_get('base_gift_cards_refunded');
    }

    /**
     * @param float $baseGiftCardsRefunded
     * @return $this
     */
    public function setBaseGiftCardsRefunded($baseGiftCardsRefunded)
    {
        $this->setData('base_gift_cards_refunded', $baseGiftCardsRefunded);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getGiftCardsRefunded()
    {
        return $this->_get('gift_cards_refunded');
    }

    /**
     * @param float $giftCardsRefunded
     * @return $this
     */
    public function setGiftCardsRefunded($giftCardsRefunded)
    {
        $this->setData('gift_cards_refunded', $giftCardsRefunded);
        return $this;
    }

    /**
     * @return \Magento\GiftMessage\Api\Data\MessageInterface|null
     */
    public function getGiftMessage()
    {
        return $this->_get('gift_message');
    }

    /**
     * @param \Magento\GiftMessage\Api\Data\MessageInterface $giftMessage
     * @return $this
     */
    public function setGiftMessage(\Magento\GiftMessage\Api\Data\MessageInterface $giftMessage)
    {
        $this->setData('gift_message', $giftMessage);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwId()
    {
        return $this->_get('gw_id');
    }

    /**
     * @param string $gwId
     * @return $this
     */
    public function setGwId($gwId)
    {
        $this->setData('gw_id', $gwId);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwAllowGiftReceipt()
    {
        return $this->_get('gw_allow_gift_receipt');
    }

    /**
     * @param string $gwAllowGiftReceipt
     * @return $this
     */
    public function setGwAllowGiftReceipt($gwAllowGiftReceipt)
    {
        $this->setData('gw_allow_gift_receipt', $gwAllowGiftReceipt);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwAddCard()
    {
        return $this->_get('gw_add_card');
    }

    /**
     * @param string $gwAddCard
     * @return $this
     */
    public function setGwAddCard($gwAddCard)
    {
        $this->setData('gw_add_card', $gwAddCard);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBasePrice()
    {
        return $this->_get('gw_base_price');
    }

    /**
     * @param string $gwBasePrice
     * @return $this
     */
    public function setGwBasePrice($gwBasePrice)
    {
        $this->setData('gw_base_price', $gwBasePrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwPrice()
    {
        return $this->_get('gw_price');
    }

    /**
     * @param string $gwPrice
     * @return $this
     */
    public function setGwPrice($gwPrice)
    {
        $this->setData('gw_price', $gwPrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBasePrice()
    {
        return $this->_get('gw_items_base_price');
    }

    /**
     * @param string $gwItemsBasePrice
     * @return $this
     */
    public function setGwItemsBasePrice($gwItemsBasePrice)
    {
        $this->setData('gw_items_base_price', $gwItemsBasePrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsPrice()
    {
        return $this->_get('gw_items_price');
    }

    /**
     * @param string $gwItemsPrice
     * @return $this
     */
    public function setGwItemsPrice($gwItemsPrice)
    {
        $this->setData('gw_items_price', $gwItemsPrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBasePrice()
    {
        return $this->_get('gw_card_base_price');
    }

    /**
     * @param string $gwCardBasePrice
     * @return $this
     */
    public function setGwCardBasePrice($gwCardBasePrice)
    {
        $this->setData('gw_card_base_price', $gwCardBasePrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardPrice()
    {
        return $this->_get('gw_card_price');
    }

    /**
     * @param string $gwCardPrice
     * @return $this
     */
    public function setGwCardPrice($gwCardPrice)
    {
        $this->setData('gw_card_price', $gwCardPrice);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmount()
    {
        return $this->_get('gw_base_tax_amount');
    }

    /**
     * @param string $gwBaseTaxAmount
     * @return $this
     */
    public function setGwBaseTaxAmount($gwBaseTaxAmount)
    {
        $this->setData('gw_base_tax_amount', $gwBaseTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwTaxAmount()
    {
        return $this->_get('gw_tax_amount');
    }

    /**
     * @param string $gwTaxAmount
     * @return $this
     */
    public function setGwTaxAmount($gwTaxAmount)
    {
        $this->setData('gw_tax_amount', $gwTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxAmount()
    {
        return $this->_get('gw_items_base_tax_amount');
    }

    /**
     * @param string $gwItemsBaseTaxAmount
     * @return $this
     */
    public function setGwItemsBaseTaxAmount($gwItemsBaseTaxAmount)
    {
        $this->setData('gw_items_base_tax_amount', $gwItemsBaseTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsTaxAmount()
    {
        return $this->_get('gw_items_tax_amount');
    }

    /**
     * @param string $gwItemsTaxAmount
     * @return $this
     */
    public function setGwItemsTaxAmount($gwItemsTaxAmount)
    {
        $this->setData('gw_items_tax_amount', $gwItemsTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxAmount()
    {
        return $this->_get('gw_card_base_tax_amount');
    }

    /**
     * @param string $gwCardBaseTaxAmount
     * @return $this
     */
    public function setGwCardBaseTaxAmount($gwCardBaseTaxAmount)
    {
        $this->setData('gw_card_base_tax_amount', $gwCardBaseTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardTaxAmount()
    {
        return $this->_get('gw_card_tax_amount');
    }

    /**
     * @param string $gwCardTaxAmount
     * @return $this
     */
    public function setGwCardTaxAmount($gwCardTaxAmount)
    {
        $this->setData('gw_card_tax_amount', $gwCardTaxAmount);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBasePriceInclTax()
    {
        return $this->_get('gw_base_price_incl_tax');
    }

    /**
     * @param string $gwBasePriceInclTax
     * @return $this
     */
    public function setGwBasePriceInclTax($gwBasePriceInclTax)
    {
        $this->setData('gw_base_price_incl_tax', $gwBasePriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwPriceInclTax()
    {
        return $this->_get('gw_price_incl_tax');
    }

    /**
     * @param string $gwPriceInclTax
     * @return $this
     */
    public function setGwPriceInclTax($gwPriceInclTax)
    {
        $this->setData('gw_price_incl_tax', $gwPriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceInclTax()
    {
        return $this->_get('gw_items_base_price_incl_tax');
    }

    /**
     * @param string $gwItemsBasePriceInclTax
     * @return $this
     */
    public function setGwItemsBasePriceInclTax($gwItemsBasePriceInclTax)
    {
        $this->setData('gw_items_base_price_incl_tax', $gwItemsBasePriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsPriceInclTax()
    {
        return $this->_get('gw_items_price_incl_tax');
    }

    /**
     * @param string $gwItemsPriceInclTax
     * @return $this
     */
    public function setGwItemsPriceInclTax($gwItemsPriceInclTax)
    {
        $this->setData('gw_items_price_incl_tax', $gwItemsPriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBasePriceInclTax()
    {
        return $this->_get('gw_card_base_price_incl_tax');
    }

    /**
     * @param string $gwCardBasePriceInclTax
     * @return $this
     */
    public function setGwCardBasePriceInclTax($gwCardBasePriceInclTax)
    {
        $this->setData('gw_card_base_price_incl_tax', $gwCardBasePriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardPriceInclTax()
    {
        return $this->_get('gw_card_price_incl_tax');
    }

    /**
     * @param string $gwCardPriceInclTax
     * @return $this
     */
    public function setGwCardPriceInclTax($gwCardPriceInclTax)
    {
        $this->setData('gw_card_price_incl_tax', $gwCardPriceInclTax);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBasePriceInvoiced()
    {
        return $this->_get('gw_base_price_invoiced');
    }

    /**
     * @param string $gwBasePriceInvoiced
     * @return $this
     */
    public function setGwBasePriceInvoiced($gwBasePriceInvoiced)
    {
        $this->setData('gw_base_price_invoiced', $gwBasePriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwPriceInvoiced()
    {
        return $this->_get('gw_price_invoiced');
    }

    /**
     * @param string $gwPriceInvoiced
     * @return $this
     */
    public function setGwPriceInvoiced($gwPriceInvoiced)
    {
        $this->setData('gw_price_invoiced', $gwPriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceInvoiced()
    {
        return $this->_get('gw_items_base_price_invoiced');
    }

    /**
     * @param string $gwItemsBasePriceInvoiced
     * @return $this
     */
    public function setGwItemsBasePriceInvoiced($gwItemsBasePriceInvoiced)
    {
        $this->setData('gw_items_base_price_invoiced', $gwItemsBasePriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsPriceInvoiced()
    {
        return $this->_get('gw_items_price_invoiced');
    }

    /**
     * @param string $gwItemsPriceInvoiced
     * @return $this
     */
    public function setGwItemsPriceInvoiced($gwItemsPriceInvoiced)
    {
        $this->setData('gw_items_price_invoiced', $gwItemsPriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBasePriceInvoiced()
    {
        return $this->_get('gw_card_base_price_invoiced');
    }

    /**
     * @param string $gwCardBasePriceInvoiced
     * @return $this
     */
    public function setGwCardBasePriceInvoiced($gwCardBasePriceInvoiced)
    {
        $this->setData('gw_card_base_price_invoiced', $gwCardBasePriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardPriceInvoiced()
    {
        return $this->_get('gw_card_price_invoiced');
    }

    /**
     * @param string $gwCardPriceInvoiced
     * @return $this
     */
    public function setGwCardPriceInvoiced($gwCardPriceInvoiced)
    {
        $this->setData('gw_card_price_invoiced', $gwCardPriceInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmountInvoiced()
    {
        return $this->_get('gw_base_tax_amount_invoiced');
    }

    /**
     * @param string $gwBaseTaxAmountInvoiced
     * @return $this
     */
    public function setGwBaseTaxAmountInvoiced($gwBaseTaxAmountInvoiced)
    {
        $this->setData('gw_base_tax_amount_invoiced', $gwBaseTaxAmountInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwTaxAmountInvoiced()
    {
        return $this->_get('gw_tax_amount_invoiced');
    }

    /**
     * @param string $gwTaxAmountInvoiced
     * @return $this
     */
    public function setGwTaxAmountInvoiced($gwTaxAmountInvoiced)
    {
        $this->setData('gw_tax_amount_invoiced', $gwTaxAmountInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxInvoiced()
    {
        return $this->_get('gw_items_base_tax_invoiced');
    }

    /**
     * @param string $gwItemsBaseTaxInvoiced
     * @return $this
     */
    public function setGwItemsBaseTaxInvoiced($gwItemsBaseTaxInvoiced)
    {
        $this->setData('gw_items_base_tax_invoiced', $gwItemsBaseTaxInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsTaxInvoiced()
    {
        return $this->_get('gw_items_tax_invoiced');
    }

    /**
     * @param string $gwItemsTaxInvoiced
     * @return $this
     */
    public function setGwItemsTaxInvoiced($gwItemsTaxInvoiced)
    {
        $this->setData('gw_items_tax_invoiced', $gwItemsTaxInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxInvoiced()
    {
        return $this->_get('gw_card_base_tax_invoiced');
    }

    /**
     * @param string $gwCardBaseTaxInvoiced
     * @return $this
     */
    public function setGwCardBaseTaxInvoiced($gwCardBaseTaxInvoiced)
    {
        $this->setData('gw_card_base_tax_invoiced', $gwCardBaseTaxInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardTaxInvoiced()
    {
        return $this->_get('gw_card_tax_invoiced');
    }

    /**
     * @param string $gwCardTaxInvoiced
     * @return $this
     */
    public function setGwCardTaxInvoiced($gwCardTaxInvoiced)
    {
        $this->setData('gw_card_tax_invoiced', $gwCardTaxInvoiced);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBasePriceRefunded()
    {
        return $this->_get('gw_base_price_refunded');
    }

    /**
     * @param string $gwBasePriceRefunded
     * @return $this
     */
    public function setGwBasePriceRefunded($gwBasePriceRefunded)
    {
        $this->setData('gw_base_price_refunded', $gwBasePriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwPriceRefunded()
    {
        return $this->_get('gw_price_refunded');
    }

    /**
     * @param string $gwPriceRefunded
     * @return $this
     */
    public function setGwPriceRefunded($gwPriceRefunded)
    {
        $this->setData('gw_price_refunded', $gwPriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceRefunded()
    {
        return $this->_get('gw_items_base_price_refunded');
    }

    /**
     * @param string $gwItemsBasePriceRefunded
     * @return $this
     */
    public function setGwItemsBasePriceRefunded($gwItemsBasePriceRefunded)
    {
        $this->setData('gw_items_base_price_refunded', $gwItemsBasePriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsPriceRefunded()
    {
        return $this->_get('gw_items_price_refunded');
    }

    /**
     * @param string $gwItemsPriceRefunded
     * @return $this
     */
    public function setGwItemsPriceRefunded($gwItemsPriceRefunded)
    {
        $this->setData('gw_items_price_refunded', $gwItemsPriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBasePriceRefunded()
    {
        return $this->_get('gw_card_base_price_refunded');
    }

    /**
     * @param string $gwCardBasePriceRefunded
     * @return $this
     */
    public function setGwCardBasePriceRefunded($gwCardBasePriceRefunded)
    {
        $this->setData('gw_card_base_price_refunded', $gwCardBasePriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardPriceRefunded()
    {
        return $this->_get('gw_card_price_refunded');
    }

    /**
     * @param string $gwCardPriceRefunded
     * @return $this
     */
    public function setGwCardPriceRefunded($gwCardPriceRefunded)
    {
        $this->setData('gw_card_price_refunded', $gwCardPriceRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmountRefunded()
    {
        return $this->_get('gw_base_tax_amount_refunded');
    }

    /**
     * @param string $gwBaseTaxAmountRefunded
     * @return $this
     */
    public function setGwBaseTaxAmountRefunded($gwBaseTaxAmountRefunded)
    {
        $this->setData('gw_base_tax_amount_refunded', $gwBaseTaxAmountRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwTaxAmountRefunded()
    {
        return $this->_get('gw_tax_amount_refunded');
    }

    /**
     * @param string $gwTaxAmountRefunded
     * @return $this
     */
    public function setGwTaxAmountRefunded($gwTaxAmountRefunded)
    {
        $this->setData('gw_tax_amount_refunded', $gwTaxAmountRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxRefunded()
    {
        return $this->_get('gw_items_base_tax_refunded');
    }

    /**
     * @param string $gwItemsBaseTaxRefunded
     * @return $this
     */
    public function setGwItemsBaseTaxRefunded($gwItemsBaseTaxRefunded)
    {
        $this->setData('gw_items_base_tax_refunded', $gwItemsBaseTaxRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwItemsTaxRefunded()
    {
        return $this->_get('gw_items_tax_refunded');
    }

    /**
     * @param string $gwItemsTaxRefunded
     * @return $this
     */
    public function setGwItemsTaxRefunded($gwItemsTaxRefunded)
    {
        $this->setData('gw_items_tax_refunded', $gwItemsTaxRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxRefunded()
    {
        return $this->_get('gw_card_base_tax_refunded');
    }

    /**
     * @param string $gwCardBaseTaxRefunded
     * @return $this
     */
    public function setGwCardBaseTaxRefunded($gwCardBaseTaxRefunded)
    {
        $this->setData('gw_card_base_tax_refunded', $gwCardBaseTaxRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGwCardTaxRefunded()
    {
        return $this->_get('gw_card_tax_refunded');
    }

    /**
     * @param string $gwCardTaxRefunded
     * @return $this
     */
    public function setGwCardTaxRefunded($gwCardTaxRefunded)
    {
        $this->setData('gw_card_tax_refunded', $gwCardTaxRefunded);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPickupLocationCode()
    {
        return $this->_get('pickup_location_code');
    }

    /**
     * @param string $pickupLocationCode
     * @return $this
     */
    public function setPickupLocationCode($pickupLocationCode)
    {
        $this->setData('pickup_location_code', $pickupLocationCode);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getNotificationSent()
    {
        return $this->_get('notification_sent');
    }

    /**
     * @param int $notificationSent
     * @return $this
     */
    public function setNotificationSent($notificationSent)
    {
        $this->setData('notification_sent', $notificationSent);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSendNotification()
    {
        return $this->_get('send_notification');
    }

    /**
     * @param int $sendNotification
     * @return $this
     */
    public function setSendNotification($sendNotification)
    {
        $this->setData('send_notification', $sendNotification);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRewardPointsBalance()
    {
        return $this->_get('reward_points_balance');
    }

    /**
     * @param int $rewardPointsBalance
     * @return $this
     */
    public function setRewardPointsBalance($rewardPointsBalance)
    {
        $this->setData('reward_points_balance', $rewardPointsBalance);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getRewardCurrencyAmount()
    {
        return $this->_get('reward_currency_amount');
    }

    /**
     * @param float $rewardCurrencyAmount
     * @return $this
     */
    public function setRewardCurrencyAmount($rewardCurrencyAmount)
    {
        $this->setData('reward_currency_amount', $rewardCurrencyAmount);
        return $this;
    }

    /**
     * @return float|null
     */
    public function getBaseRewardCurrencyAmount()
    {
        return $this->_get('base_reward_currency_amount');
    }

    /**
     * @param float $baseRewardCurrencyAmount
     * @return $this
     */
    public function setBaseRewardCurrencyAmount($baseRewardCurrencyAmount)
    {
        $this->setData('base_reward_currency_amount', $baseRewardCurrencyAmount);
        return $this;
    }
}
