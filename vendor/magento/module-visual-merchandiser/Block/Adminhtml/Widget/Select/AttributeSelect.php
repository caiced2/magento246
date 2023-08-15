<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select;

use \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select;

/**
 * @api
 * @since 100.0.2
 */
class AttributeSelect extends Select
{
    /**
     * Get Select option values
     *
     * @return array
     */
    public function getSelectOptions()
    {
        $result = [];
        $result[""] = __('Choose a selection...');
        return array_merge($result, $this->_rules->getAvailableAttributes());
    }
}
