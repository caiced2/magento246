<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select;

use \Magento\VisualMerchandiser\Model\Rules\Rule;

/**
 * @api
 * @since 100.0.2
 */
class OperatorSelect extends \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select
{
    /**
     * Get Select option values
     *
     * @return array
     */
    public function getSelectOptions()
    {
        $options = [];
        $options[''] = __('Choose a selection...');
        return array_merge($options, Rule::getOperators());
    }
}
