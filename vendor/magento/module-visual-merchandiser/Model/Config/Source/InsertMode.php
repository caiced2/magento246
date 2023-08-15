<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Config\Source;

/**
 * Class InsertMode
 * @package Magento\VisualMerchandiser\Model\Config\Source
 * @api
 * @since 100.0.2
 */
class InsertMode implements \Magento\Framework\Option\ArrayInterface
{
    const XML_PATH_INSERT_MODE = 'visualmerchandiser/options/insert_mode';

    /**
     * Products added via mass product assignment will be added to the top
     */
    const INSERT_MODE_TOP = 0;

    /**
     * Products added via mass product assignment will be added to the bottom
     */
    const INSERT_MODE_BOTTOM = 1;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::INSERT_MODE_TOP, 'label' => __('On Top')],
            ['value' => self::INSERT_MODE_BOTTOM, 'label' => __('On Bottom')]
        ];
    }
}
