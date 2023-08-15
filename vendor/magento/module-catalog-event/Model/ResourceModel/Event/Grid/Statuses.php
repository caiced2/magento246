<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Catalog Event statuses option array
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\CatalogEvent\Model\ResourceModel\Event\Grid;

class Statuses implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return statuses option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $statusMapper = \Magento\CatalogEvent\Model\Event::$statusMapper;

        return [
            $statusMapper[\Magento\CatalogEvent\Model\Event::STATUS_UPCOMING] => __('Upcoming'),
            $statusMapper[\Magento\CatalogEvent\Model\Event::STATUS_OPEN] => __('Open'),
            $statusMapper[\Magento\CatalogEvent\Model\Event::STATUS_CLOSED] => __('Closed')
        ];
    }
}
