<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Block\Adminhtml\Order\Create;

/**
 * Gift wrapping order create items block
 *
 * @api
 * @since 100.0.2
 */
class Items extends \Magento\GiftWrapping\Block\Adminhtml\Order\Create\AbstractCreate
{
    /**
     * Select element for choosing gift wrapping design
     *
     * @return array
     */
    public function getDesignSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            ['id' => 'giftwrapping_design_item', 'class' => 'select admin__control-select']
        )->setOptions(
            $this->getDesignCollection()->toOptionArray()
        );
        return $select->getHtml();
    }

    /**
     * Prepare and return quote items info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getItemsInfo()
    {
        $data = [];
        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }
            if ($this->getDisplayGiftWrappingForItem($item)) {
                $temp = [];
                if ($price = $item->getProduct()->getGiftWrappingPrice()) {
                    if ($this->getDisplayWrappingBothPrices()) {
                        $temp['price_incl_tax'] = $this->escapeHtml(
                            $this->calculatePrice(new \Magento\Framework\DataObject(), $price, true)
                        );
                        $temp['price_excl_tax'] = $this->escapeHtml(
                            $this->calculatePrice(new \Magento\Framework\DataObject(), $price)
                        );
                    } else {
                        $temp['price'] = $this->escapeHtml(
                            $this->calculatePrice(
                                new \Magento\Framework\DataObject(),
                                $price,
                                $this->getDisplayWrappingPriceInclTax()
                            )
                        );
                    }
                }
                $temp['design'] = $this->escapeHtml($item->getGwId());
                $data[$item->getId()] = $temp;
            }
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Check ability to display gift wrapping for items during backend order create
     *
     * @return bool
     */
    public function canDisplayGiftWrappingForItems()
    {
        $canDisplay = false;
        $count = count($this->getDesignCollection());
        if ($count) {
            foreach ($this->getQuote()->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($this->getDisplayGiftWrappingForItem($item)) {
                    $canDisplay = true;
                }
            }
        }
        return $canDisplay;
    }

    /**
     * Check ability to display gift wrapping for quote item
     *
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getDisplayGiftWrappingForItem($item)
    {
        $allowed = $item->getProduct()->getGiftWrappingAvailable();
        return $this->_giftWrappingData->isGiftWrappingAvailableForProduct($allowed, $this->getStoreId());
    }
}
