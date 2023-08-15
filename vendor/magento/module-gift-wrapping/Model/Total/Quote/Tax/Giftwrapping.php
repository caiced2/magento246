<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model\Total\Quote\Tax;

use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * GiftWrapping tax total calculator for quote
 */
class Giftwrapping extends AbstractTotal
{
    /**
     * Constant for item gift wrapping item type
     */
    const ITEM_TYPE = 'item_gw';

    /**
     * Constant for quote gift wrapping item type
     */
    const QUOTE_TYPE = 'quote_gw';

    /**
     * Constant for item print card item type
     */
    const PRINTED_CARD_TYPE = 'printed_card_gw';

    /**
     * Constant for item gift wrapping code prefix
     */
    const CODE_ITEM_GW_PREFIX = 'item_gw';

    /**
     * Constant for quote gift wrapping code
     */
    const CODE_QUOTE_GW = 'quote_gw';

    /**
     * Constant for printed card code
     */
    const CODE_PRINTED_CARD = 'printed_card_gw';

    /**
     * @var \Magento\Quote\Model\Quote|\Magento\Quote\Model\Quote\Address
     */
    protected $_quoteEntity;

    /**
     * Gift wrapping data
     *
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_giftWrappingData;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\GiftWrapping\Model\WrappingFactory
     */
    protected $wrappingFactory;

    /**
     * @var \Magento\Store\Model\Store
     */
    protected $store;

    /**
     * @var int
     */
    protected $counter = 0;

    /**
     * @param \Magento\GiftWrapping\Helper\Data $giftWrappingData
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
     */
    public function __construct(
        \Magento\GiftWrapping\Helper\Data $giftWrappingData,
        PriceCurrencyInterface $priceCurrency,
        \Magento\GiftWrapping\Model\WrappingFactory $wrappingFactory
    ) {
        $this->_giftWrappingData = $giftWrappingData;
        $this->priceCurrency = $priceCurrency;
        $this->wrappingFactory = $wrappingFactory;
        $this->setCode('pretax_giftwrapping');
    }

    /**
     * Collect gift wrapping related items and add them to tax calculation
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        if ($shippingAssignment->getShipping()->getAddress()->getAddressType() != Address::TYPE_SHIPPING) {
            return $this;
        }

        $this->_quoteEntity = $quote;

        $this->store = $this->_quoteEntity->getStore();
        $productTaxClassId = $this->_giftWrappingData->getWrappingTaxClass($this->store);

        $this->_collectWrappingForItems($shippingAssignment, $total, $productTaxClassId);
        $this->_collectWrappingForQuote($shippingAssignment, $productTaxClassId);
        $this->_collectPrintedCard($shippingAssignment, $productTaxClassId);

        return $this;
    }

    /**
     * Collect wrapping tax total for items
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @param   int $gwTaxClassId
     * @return  $this
     */
    protected function _collectWrappingForItems($shippingAssignment, $total, $gwTaxClassId)
    {
        $gwItemCodeToItemMapping = [];

        foreach ($shippingAssignment->getItems() as $item) {
            $itemGwId = $item->getGwId();
            if ($item->getProduct()->isVirtual() || $item->getParentItem() || !$itemGwId) {
                continue;
            }
            $associatedTaxables = [];

            if ($item->getProduct()->getGiftWrappingPrice()) {
                $gwBasePrice = $item->getProduct()->getGiftWrappingPrice();
            } else {
                /** @var \Magento\GiftWrapping\Model\Wrapping $wrapping */
                $wrapping = $this->wrappingFactory->create();
                $wrapping->setStoreId($this->store->getId())->load($itemGwId);
                $gwBasePrice = $wrapping->getBasePrice();
            }

            $gwPrice = $this->priceCurrency->convert($gwBasePrice, $this->store);
            $gwItemCode = self::CODE_ITEM_GW_PREFIX . $this->getNextIncrement();

            $gwItemCodeToItemMapping[$gwItemCode] = $item;

            $associatedTaxables[] = [
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => self::ITEM_TYPE,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => $gwItemCode,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $gwPrice,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $gwBasePrice,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => $item->getQty(),
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $gwTaxClassId,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => false,
            ];

            $item->setAssociatedTaxables($associatedTaxables);
        }

        $total->setGwItemCodeToItemMapping($gwItemCodeToItemMapping);

        return $this;
    }

    /**
     * Collect wrapping tax total for quote
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param int $gwTaxClassId
     * @return $this
     */
    protected function _collectWrappingForQuote($shippingAssignment, $gwTaxClassId)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        $gwId = $address->getGwId();
        if ($gwId) {
            $associatedTaxables = $address->getAssociatedTaxables();
            if (!$associatedTaxables) {
                $associatedTaxables = [];
            }

            /** @var \Magento\GiftWrapping\Model\Wrapping $wrapping */
            $wrapping = $this->wrappingFactory->create();
            $wrapping->setStoreId($this->store->getId())->load($gwId);

            $wrappingBaseAmount = $wrapping->getBasePrice();
            $wrappingAmount = $this->priceCurrency->convert($wrappingBaseAmount, $this->store);

            $associatedTaxables[] = [
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => self::QUOTE_TYPE,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => self::CODE_QUOTE_GW,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $wrappingAmount,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $wrappingBaseAmount,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $gwTaxClassId,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => false,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE
                => CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE,
            ];

            $address->setAssociatedTaxables($associatedTaxables);
        }
        return $this;
    }

    /**
     * Collect printed card tax total for quote
     *
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param int $gwTaxClassId
     * @return $this
     */
    protected function _collectPrintedCard($shippingAssignment, $gwTaxClassId)
    {
        $address = $shippingAssignment->getShipping()->getAddress();
        if ($this->_quoteEntity->getGwAddCard()) {
            $associatedTaxables = $address->getAssociatedTaxables();
            if (!$associatedTaxables) {
                $associatedTaxables = [];
            }

            $printedCardBaseTaxAmount = $this->_giftWrappingData->getPrintedCardPrice($this->_quoteEntity->getStore());
            $printedCardTaxAmount = $this->priceCurrency->convert(
                $printedCardBaseTaxAmount,
                $this->_quoteEntity->getStore()
            );

            $associatedTaxables[] = [
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => self::PRINTED_CARD_TYPE,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => self::CODE_PRINTED_CARD,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $printedCardTaxAmount,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $printedCardBaseTaxAmount,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $gwTaxClassId,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => false,
                CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE
                => CommonTaxCollector::ASSOCIATION_ITEM_CODE_FOR_QUOTE,
            ];

            $address->setAssociatedTaxables($associatedTaxables);
        }
        return $this;
    }

    /**
     * Assign wrapping tax totals and labels to address object
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address\Total $total
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        return null;
    }

    /**
     * Increment and return counter. This function is intended to be used to generate temporary
     * id for an item.
     *
     * @return int
     */
    protected function getNextIncrement()
    {
        return ++$this->counter;
    }
}
