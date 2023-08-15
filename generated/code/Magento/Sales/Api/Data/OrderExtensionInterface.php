<?php
namespace Magento\Sales\Api\Data;

/**
 * ExtensionInterface class for @see \Magento\Sales\Api\Data\OrderInterface
 */
interface OrderExtensionInterface extends \Magento\Framework\Api\ExtensionAttributesInterface
{
    /**
     * @return \Magento\Sales\Api\Data\ShippingAssignmentInterface[]|null
     */
    public function getShippingAssignments();

    /**
     * @param \Magento\Sales\Api\Data\ShippingAssignmentInterface[] $shippingAssignments
     * @return $this
     */
    public function setShippingAssignments($shippingAssignments);

    /**
     * @return \Magento\Payment\Api\Data\PaymentAdditionalInfoInterface[]|null
     */
    public function getPaymentAdditionalInfo();

    /**
     * @param \Magento\Payment\Api\Data\PaymentAdditionalInfoInterface[] $paymentAdditionalInfo
     * @return $this
     */
    public function setPaymentAdditionalInfo($paymentAdditionalInfo);

    /**
     * @return \Magento\Tax\Api\Data\OrderTaxDetailsAppliedTaxInterface[]|null
     */
    public function getAppliedTaxes();

    /**
     * @param \Magento\Tax\Api\Data\OrderTaxDetailsAppliedTaxInterface[] $appliedTaxes
     * @return $this
     */
    public function setAppliedTaxes($appliedTaxes);

    /**
     * @return \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface[]|null
     */
    public function getItemAppliedTaxes();

    /**
     * @param \Magento\Tax\Api\Data\OrderTaxDetailsItemInterface[] $itemAppliedTaxes
     * @return $this
     */
    public function setItemAppliedTaxes($itemAppliedTaxes);

    /**
     * @return boolean|null
     */
    public function getConvertingFromQuote();

    /**
     * @param boolean $convertingFromQuote
     * @return $this
     */
    public function setConvertingFromQuote($convertingFromQuote);

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceAmount();

    /**
     * @param float $baseCustomerBalanceAmount
     * @return $this
     */
    public function setBaseCustomerBalanceAmount($baseCustomerBalanceAmount);

    /**
     * @return float|null
     */
    public function getCustomerBalanceAmount();

    /**
     * @param float $customerBalanceAmount
     * @return $this
     */
    public function setCustomerBalanceAmount($customerBalanceAmount);

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceInvoiced();

    /**
     * @param float $baseCustomerBalanceInvoiced
     * @return $this
     */
    public function setBaseCustomerBalanceInvoiced($baseCustomerBalanceInvoiced);

    /**
     * @return float|null
     */
    public function getCustomerBalanceInvoiced();

    /**
     * @param float $customerBalanceInvoiced
     * @return $this
     */
    public function setCustomerBalanceInvoiced($customerBalanceInvoiced);

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceRefunded();

    /**
     * @param float $baseCustomerBalanceRefunded
     * @return $this
     */
    public function setBaseCustomerBalanceRefunded($baseCustomerBalanceRefunded);

    /**
     * @return float|null
     */
    public function getCustomerBalanceRefunded();

    /**
     * @param float $customerBalanceRefunded
     * @return $this
     */
    public function setCustomerBalanceRefunded($customerBalanceRefunded);

    /**
     * @return float|null
     */
    public function getBaseCustomerBalanceTotalRefunded();

    /**
     * @param float $baseCustomerBalanceTotalRefunded
     * @return $this
     */
    public function setBaseCustomerBalanceTotalRefunded($baseCustomerBalanceTotalRefunded);

    /**
     * @return float|null
     */
    public function getCustomerBalanceTotalRefunded();

    /**
     * @param float $customerBalanceTotalRefunded
     * @return $this
     */
    public function setCustomerBalanceTotalRefunded($customerBalanceTotalRefunded);

    /**
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardInterface[]|null
     */
    public function getGiftCards();

    /**
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardInterface[] $giftCards
     * @return $this
     */
    public function setGiftCards($giftCards);

    /**
     * @return float|null
     */
    public function getBaseGiftCardsAmount();

    /**
     * @param float $baseGiftCardsAmount
     * @return $this
     */
    public function setBaseGiftCardsAmount($baseGiftCardsAmount);

    /**
     * @return float|null
     */
    public function getGiftCardsAmount();

    /**
     * @param float $giftCardsAmount
     * @return $this
     */
    public function setGiftCardsAmount($giftCardsAmount);

    /**
     * @return float|null
     */
    public function getBaseGiftCardsInvoiced();

    /**
     * @param float $baseGiftCardsInvoiced
     * @return $this
     */
    public function setBaseGiftCardsInvoiced($baseGiftCardsInvoiced);

    /**
     * @return float|null
     */
    public function getGiftCardsInvoiced();

    /**
     * @param float $giftCardsInvoiced
     * @return $this
     */
    public function setGiftCardsInvoiced($giftCardsInvoiced);

    /**
     * @return float|null
     */
    public function getBaseGiftCardsRefunded();

    /**
     * @param float $baseGiftCardsRefunded
     * @return $this
     */
    public function setBaseGiftCardsRefunded($baseGiftCardsRefunded);

    /**
     * @return float|null
     */
    public function getGiftCardsRefunded();

    /**
     * @param float $giftCardsRefunded
     * @return $this
     */
    public function setGiftCardsRefunded($giftCardsRefunded);

    /**
     * @return \Magento\GiftMessage\Api\Data\MessageInterface|null
     */
    public function getGiftMessage();

    /**
     * @param \Magento\GiftMessage\Api\Data\MessageInterface $giftMessage
     * @return $this
     */
    public function setGiftMessage(\Magento\GiftMessage\Api\Data\MessageInterface $giftMessage);

    /**
     * @return string|null
     */
    public function getGwId();

    /**
     * @param string $gwId
     * @return $this
     */
    public function setGwId($gwId);

    /**
     * @return string|null
     */
    public function getGwAllowGiftReceipt();

    /**
     * @param string $gwAllowGiftReceipt
     * @return $this
     */
    public function setGwAllowGiftReceipt($gwAllowGiftReceipt);

    /**
     * @return string|null
     */
    public function getGwAddCard();

    /**
     * @param string $gwAddCard
     * @return $this
     */
    public function setGwAddCard($gwAddCard);

    /**
     * @return string|null
     */
    public function getGwBasePrice();

    /**
     * @param string $gwBasePrice
     * @return $this
     */
    public function setGwBasePrice($gwBasePrice);

    /**
     * @return string|null
     */
    public function getGwPrice();

    /**
     * @param string $gwPrice
     * @return $this
     */
    public function setGwPrice($gwPrice);

    /**
     * @return string|null
     */
    public function getGwItemsBasePrice();

    /**
     * @param string $gwItemsBasePrice
     * @return $this
     */
    public function setGwItemsBasePrice($gwItemsBasePrice);

    /**
     * @return string|null
     */
    public function getGwItemsPrice();

    /**
     * @param string $gwItemsPrice
     * @return $this
     */
    public function setGwItemsPrice($gwItemsPrice);

    /**
     * @return string|null
     */
    public function getGwCardBasePrice();

    /**
     * @param string $gwCardBasePrice
     * @return $this
     */
    public function setGwCardBasePrice($gwCardBasePrice);

    /**
     * @return string|null
     */
    public function getGwCardPrice();

    /**
     * @param string $gwCardPrice
     * @return $this
     */
    public function setGwCardPrice($gwCardPrice);

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmount();

    /**
     * @param string $gwBaseTaxAmount
     * @return $this
     */
    public function setGwBaseTaxAmount($gwBaseTaxAmount);

    /**
     * @return string|null
     */
    public function getGwTaxAmount();

    /**
     * @param string $gwTaxAmount
     * @return $this
     */
    public function setGwTaxAmount($gwTaxAmount);

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxAmount();

    /**
     * @param string $gwItemsBaseTaxAmount
     * @return $this
     */
    public function setGwItemsBaseTaxAmount($gwItemsBaseTaxAmount);

    /**
     * @return string|null
     */
    public function getGwItemsTaxAmount();

    /**
     * @param string $gwItemsTaxAmount
     * @return $this
     */
    public function setGwItemsTaxAmount($gwItemsTaxAmount);

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxAmount();

    /**
     * @param string $gwCardBaseTaxAmount
     * @return $this
     */
    public function setGwCardBaseTaxAmount($gwCardBaseTaxAmount);

    /**
     * @return string|null
     */
    public function getGwCardTaxAmount();

    /**
     * @param string $gwCardTaxAmount
     * @return $this
     */
    public function setGwCardTaxAmount($gwCardTaxAmount);

    /**
     * @return string|null
     */
    public function getGwBasePriceInclTax();

    /**
     * @param string $gwBasePriceInclTax
     * @return $this
     */
    public function setGwBasePriceInclTax($gwBasePriceInclTax);

    /**
     * @return string|null
     */
    public function getGwPriceInclTax();

    /**
     * @param string $gwPriceInclTax
     * @return $this
     */
    public function setGwPriceInclTax($gwPriceInclTax);

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceInclTax();

    /**
     * @param string $gwItemsBasePriceInclTax
     * @return $this
     */
    public function setGwItemsBasePriceInclTax($gwItemsBasePriceInclTax);

    /**
     * @return string|null
     */
    public function getGwItemsPriceInclTax();

    /**
     * @param string $gwItemsPriceInclTax
     * @return $this
     */
    public function setGwItemsPriceInclTax($gwItemsPriceInclTax);

    /**
     * @return string|null
     */
    public function getGwCardBasePriceInclTax();

    /**
     * @param string $gwCardBasePriceInclTax
     * @return $this
     */
    public function setGwCardBasePriceInclTax($gwCardBasePriceInclTax);

    /**
     * @return string|null
     */
    public function getGwCardPriceInclTax();

    /**
     * @param string $gwCardPriceInclTax
     * @return $this
     */
    public function setGwCardPriceInclTax($gwCardPriceInclTax);

    /**
     * @return string|null
     */
    public function getGwBasePriceInvoiced();

    /**
     * @param string $gwBasePriceInvoiced
     * @return $this
     */
    public function setGwBasePriceInvoiced($gwBasePriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwPriceInvoiced();

    /**
     * @param string $gwPriceInvoiced
     * @return $this
     */
    public function setGwPriceInvoiced($gwPriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceInvoiced();

    /**
     * @param string $gwItemsBasePriceInvoiced
     * @return $this
     */
    public function setGwItemsBasePriceInvoiced($gwItemsBasePriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwItemsPriceInvoiced();

    /**
     * @param string $gwItemsPriceInvoiced
     * @return $this
     */
    public function setGwItemsPriceInvoiced($gwItemsPriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwCardBasePriceInvoiced();

    /**
     * @param string $gwCardBasePriceInvoiced
     * @return $this
     */
    public function setGwCardBasePriceInvoiced($gwCardBasePriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwCardPriceInvoiced();

    /**
     * @param string $gwCardPriceInvoiced
     * @return $this
     */
    public function setGwCardPriceInvoiced($gwCardPriceInvoiced);

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmountInvoiced();

    /**
     * @param string $gwBaseTaxAmountInvoiced
     * @return $this
     */
    public function setGwBaseTaxAmountInvoiced($gwBaseTaxAmountInvoiced);

    /**
     * @return string|null
     */
    public function getGwTaxAmountInvoiced();

    /**
     * @param string $gwTaxAmountInvoiced
     * @return $this
     */
    public function setGwTaxAmountInvoiced($gwTaxAmountInvoiced);

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxInvoiced();

    /**
     * @param string $gwItemsBaseTaxInvoiced
     * @return $this
     */
    public function setGwItemsBaseTaxInvoiced($gwItemsBaseTaxInvoiced);

    /**
     * @return string|null
     */
    public function getGwItemsTaxInvoiced();

    /**
     * @param string $gwItemsTaxInvoiced
     * @return $this
     */
    public function setGwItemsTaxInvoiced($gwItemsTaxInvoiced);

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxInvoiced();

    /**
     * @param string $gwCardBaseTaxInvoiced
     * @return $this
     */
    public function setGwCardBaseTaxInvoiced($gwCardBaseTaxInvoiced);

    /**
     * @return string|null
     */
    public function getGwCardTaxInvoiced();

    /**
     * @param string $gwCardTaxInvoiced
     * @return $this
     */
    public function setGwCardTaxInvoiced($gwCardTaxInvoiced);

    /**
     * @return string|null
     */
    public function getGwBasePriceRefunded();

    /**
     * @param string $gwBasePriceRefunded
     * @return $this
     */
    public function setGwBasePriceRefunded($gwBasePriceRefunded);

    /**
     * @return string|null
     */
    public function getGwPriceRefunded();

    /**
     * @param string $gwPriceRefunded
     * @return $this
     */
    public function setGwPriceRefunded($gwPriceRefunded);

    /**
     * @return string|null
     */
    public function getGwItemsBasePriceRefunded();

    /**
     * @param string $gwItemsBasePriceRefunded
     * @return $this
     */
    public function setGwItemsBasePriceRefunded($gwItemsBasePriceRefunded);

    /**
     * @return string|null
     */
    public function getGwItemsPriceRefunded();

    /**
     * @param string $gwItemsPriceRefunded
     * @return $this
     */
    public function setGwItemsPriceRefunded($gwItemsPriceRefunded);

    /**
     * @return string|null
     */
    public function getGwCardBasePriceRefunded();

    /**
     * @param string $gwCardBasePriceRefunded
     * @return $this
     */
    public function setGwCardBasePriceRefunded($gwCardBasePriceRefunded);

    /**
     * @return string|null
     */
    public function getGwCardPriceRefunded();

    /**
     * @param string $gwCardPriceRefunded
     * @return $this
     */
    public function setGwCardPriceRefunded($gwCardPriceRefunded);

    /**
     * @return string|null
     */
    public function getGwBaseTaxAmountRefunded();

    /**
     * @param string $gwBaseTaxAmountRefunded
     * @return $this
     */
    public function setGwBaseTaxAmountRefunded($gwBaseTaxAmountRefunded);

    /**
     * @return string|null
     */
    public function getGwTaxAmountRefunded();

    /**
     * @param string $gwTaxAmountRefunded
     * @return $this
     */
    public function setGwTaxAmountRefunded($gwTaxAmountRefunded);

    /**
     * @return string|null
     */
    public function getGwItemsBaseTaxRefunded();

    /**
     * @param string $gwItemsBaseTaxRefunded
     * @return $this
     */
    public function setGwItemsBaseTaxRefunded($gwItemsBaseTaxRefunded);

    /**
     * @return string|null
     */
    public function getGwItemsTaxRefunded();

    /**
     * @param string $gwItemsTaxRefunded
     * @return $this
     */
    public function setGwItemsTaxRefunded($gwItemsTaxRefunded);

    /**
     * @return string|null
     */
    public function getGwCardBaseTaxRefunded();

    /**
     * @param string $gwCardBaseTaxRefunded
     * @return $this
     */
    public function setGwCardBaseTaxRefunded($gwCardBaseTaxRefunded);

    /**
     * @return string|null
     */
    public function getGwCardTaxRefunded();

    /**
     * @param string $gwCardTaxRefunded
     * @return $this
     */
    public function setGwCardTaxRefunded($gwCardTaxRefunded);

    /**
     * @return string|null
     */
    public function getPickupLocationCode();

    /**
     * @param string $pickupLocationCode
     * @return $this
     */
    public function setPickupLocationCode($pickupLocationCode);

    /**
     * @return int|null
     */
    public function getNotificationSent();

    /**
     * @param int $notificationSent
     * @return $this
     */
    public function setNotificationSent($notificationSent);

    /**
     * @return int|null
     */
    public function getSendNotification();

    /**
     * @param int $sendNotification
     * @return $this
     */
    public function setSendNotification($sendNotification);

    /**
     * @return int|null
     */
    public function getRewardPointsBalance();

    /**
     * @param int $rewardPointsBalance
     * @return $this
     */
    public function setRewardPointsBalance($rewardPointsBalance);

    /**
     * @return float|null
     */
    public function getRewardCurrencyAmount();

    /**
     * @param float $rewardCurrencyAmount
     * @return $this
     */
    public function setRewardCurrencyAmount($rewardCurrencyAmount);

    /**
     * @return float|null
     */
    public function getBaseRewardCurrencyAmount();

    /**
     * @param float $baseRewardCurrencyAmount
     * @return $this
     */
    public function setBaseRewardCurrencyAmount($baseRewardCurrencyAmount);
}
