<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Gift wrapping order items view block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\GiftWrapping\Block\Adminhtml\Order\View;

/**
 * @api
 * @since 100.0.2
 */
class Items extends \Magento\GiftWrapping\Block\Adminhtml\Order\View\AbstractView
{
    /**
     * Prepare and return order items info
     *
     * @return \Magento\Framework\DataObject
     */
    public function getItemsInfo()
    {
        $data = [];
        foreach ($this->getOrder()->getAllItems() as $item) {
            if ($this->getDisplayWrappingBothPrices()) {
                $temp['price_excl_tax'] = $this->_preparePrices($item->getGwBasePrice(), $item->getGwPrice());
                $temp['price_incl_tax'] = $this->_preparePrices(
                    $item->getGwBasePrice() + $item->getGwBaseTaxAmount(),
                    $item->getGwPrice() + $item->getGwTaxAmount()
                );
            } elseif ($this->getDisplayWrappingPriceInclTax()) {
                $temp['price'] = $this->_preparePrices(
                    $item->getGwBasePrice() + $item->getGwBaseTaxAmount(),
                    $item->getGwPrice() + $item->getGwTaxAmount()
                );
            } else {
                $temp['price'] = $this->_preparePrices($item->getGwBasePrice(), $item->getGwPrice());
            }
            $temp['design'] = $item->getGwId();
            $data[$item->getId()] = $temp;
        }
        return new \Magento\Framework\DataObject($data);
    }

    /**
     * Check ability to display gift wrapping for order items
     *
     * @return bool
     */
    public function canDisplayGiftWrappingForItems()
    {
        foreach ($this->getOrder()->getAllItems() as $item) {
            if ($item->getGwId()) {
                return true;
            }
        }
        return false;
    }
}
