<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * TargetRule Products Item Block
 *
 *
 * @method \Magento\TargetRule\Block\Catalog\Product\Item setItem(\Magento\Catalog\Model\Product $item)
 * @method \Magento\Catalog\Model\Product getItem()
 */
namespace Magento\TargetRule\Block\Catalog\Product;

class Item extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * Get cache key informative items with the position number to differentiate
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKeyInfo = parent::getCacheKeyInfo();

        $cacheKeyInfo[] = $this->getPosition();

        return $cacheKeyInfo;
    }
}
