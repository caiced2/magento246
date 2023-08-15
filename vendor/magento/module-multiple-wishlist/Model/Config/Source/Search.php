<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Search config model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\MultipleWishlist\Model\Config\Source;

class Search implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Quick search form types
     */
    const WISHLIST_SEARCH_DISPLAY_ALL_FORMS = 'all';

    const WISHLIST_SEARCH_DISPLAY_NAME_FORM = 'name';

    const WISHLIST_SEARCH_DISPLAY_EMAIL_FORM = 'email';

    /**
     * Retrieve search form types as option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->getTypes() as $key => $label) {
            $result[] = ['value' => $key, 'label' => $label];
        }
        return $result;
    }

    /**
     * Retrieve array of search form types
     *
     * @return array
     */
    public function getTypes()
    {
        return [
            self::WISHLIST_SEARCH_DISPLAY_ALL_FORMS => __('All Forms'),
            self::WISHLIST_SEARCH_DISPLAY_NAME_FORM => __('Owner Name'),
            self::WISHLIST_SEARCH_DISPLAY_EMAIL_FORM => __('Owner Email')
        ];
    }
}
