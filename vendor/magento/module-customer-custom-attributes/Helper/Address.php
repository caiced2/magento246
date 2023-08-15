<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Enterprise Customer Data Helper
 *
 */
namespace Magento\CustomerCustomAttributes\Helper;

class Address extends \Magento\CustomAttributeManagement\Helper\Data
{
    /**
     * Default attribute entity type code
     *
     * @return string
     */
    protected function _getEntityTypeCode()
    {
        return 'customer_address';
    }

    /**
     * Return available customer address attribute form as select options
     *
     * @return array
     */
    public function getAttributeFormOptions()
    {
        return [
            ['label' => __('Customer Address Registration'), 'value' => 'customer_register_address'],
            ['label' => __('Customer Account Address'), 'value' => 'customer_address_edit']
        ];
    }
}
