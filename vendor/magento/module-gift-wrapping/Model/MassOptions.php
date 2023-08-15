<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Model;

/**
 * User statuses option array
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 * @codeCoverageIgnore
 */
class MassOptions implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return statuses array
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => '', 'value' => ''],
            ['label' => __('Enabled'), 'value' => '1'],
            ['label' => __('Disabled'), 'value' => '0']
        ];
    }
}
