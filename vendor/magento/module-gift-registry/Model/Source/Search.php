<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Source;

/**
 * Search source model
 * @codeCoverageIgnore
 */
class Search implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Quick search form types
     */
    const SEARCH_ALL_FORM = 'all';

    const SEARCH_NAME_FORM = 'name';

    const SEARCH_EMAIL_FORM = 'email';

    const SEARCH_ID_FORM = 'id';

    /**
     * Return search form types as option array
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
     * Return array of search form types
     *
     * @return array
     */
    public function getTypes()
    {
        return [
            self::SEARCH_ALL_FORM => __('All Forms'),
            self::SEARCH_NAME_FORM => __('Registrant Name Search'),
            self::SEARCH_EMAIL_FORM => __('Registrant Email Search'),
            self::SEARCH_ID_FORM => __('Gift Registry ID Search')
        ];
    }
}
