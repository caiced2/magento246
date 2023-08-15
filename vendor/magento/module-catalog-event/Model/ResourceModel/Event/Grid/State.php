<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Event  statuses option array
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\CatalogEvent\Model\ResourceModel\Event\Grid;

class State implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return catalog event array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            0 => __('Lister Block'),
            \Magento\CatalogEvent\Model\Event::DISPLAY_CATEGORY_PAGE => __('Category Page'),
            \Magento\CatalogEvent\Model\Event::DISPLAY_PRODUCT_PAGE => __('Product Page')
        ];
    }
}
