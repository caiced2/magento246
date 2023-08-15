<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model\System\Config\Source;

/**
 * Sys config source model for private sales redirect modes
 *
 * @api
 * @since 100.0.2
 */
class Redirect extends \Magento\Framework\DataObject implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Get options for select
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Magento\WebsiteRestriction\Model\Mode::HTTP_302_LOGIN,
                'label' => __('To login form (302 Found)'),
            ],
            [
                'value' => \Magento\WebsiteRestriction\Model\Mode::HTTP_302_LANDING,
                'label' => __('To landing page (302 Found)')
            ]
        ];
    }
}
