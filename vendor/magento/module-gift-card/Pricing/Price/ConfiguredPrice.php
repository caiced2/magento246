<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Pricing\Price;

use Magento\Catalog\Pricing\Price\ConfiguredPrice as CatalogConfiguredPrice;
use Magento\Catalog\Pricing\Price\ConfiguredPriceInterface;
use Magento\Framework\Pricing\Amount\AmountInterface;

class ConfiguredPrice extends CatalogConfiguredPrice implements ConfiguredPriceInterface
{
    /**
     * Calculate configured price
     *
     * @return float
     */
    protected function calculatePrice()
    {
        $value = $this->getProduct()->getPrice();
        if ($this->getProduct()->hasCustomOptions()) {
            /** @var \Magento\Wishlist\Model\Item\Option $customOption */
            $customOption = $this->getProduct()
                ->getCustomOption('giftcard_amount');
            if ($customOption) {
                $value = ($customOption->getValue() ? $customOption->getValue() : 0.);
            }
        }
        $value += parent::getOptionsValue();
        return $value;
    }

    /**
     * Price value of product with configured options
     *
     * @return bool|float
     */
    public function getValue()
    {
        return $this->item ? $this->calculatePrice() : max(0, $this->getBasePrice()->getValue());
    }
}
