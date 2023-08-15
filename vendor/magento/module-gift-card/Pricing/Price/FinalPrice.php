<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Pricing\Price;

use Magento\Catalog\Pricing\Price\FinalPrice as CatalogFinalPrice;

/**
 * Final price model
 */
class FinalPrice extends CatalogFinalPrice
{
    /**
     * @var array
     */
    protected $amountsCache = [];

    /**
     * @return array
     */
    public function getAmounts()
    {
        if (!empty($this->amountsCache)) {
            return $this->amountsCache;
        }

        foreach ($this->product->getGiftcardAmounts() as $amount) {
            $this->amountsCache[] = $this->priceCurrency->convertAndRound($amount['website_value']);
        }
        sort($this->amountsCache);
        return $this->amountsCache;
    }

    /**
     * Get Value
     *
     * @return float|bool
     */
    public function getValue()
    {
        $amount = $this->getAmounts();
        return count($amount) >= 1 ? array_shift($amount) : false;
    }
}
