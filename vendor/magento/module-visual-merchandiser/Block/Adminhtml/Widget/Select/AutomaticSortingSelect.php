<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select;

use \Magento\VisualMerchandiser\Block\Adminhtml\Widget\Select;
use \Magento\VisualMerchandiser\Model\Sorting;

/**
 * @api
 * @since 100.0.2
 */
class AutomaticSortingSelect extends Select
{
    /**
     * Get Select option values
     *
     * @return array
     */
    public function getSelectOptions()
    {
        return $this->_sorting->getSortingOptions();
    }

    /**
     * Get current value
     *
     * @return string
     */
    public function getSelectValue()
    {
        $category = $this->_registry->registry('current_category');
        if ($category) {
            return $category->getAutomaticSorting();
        }
        return "";
    }
}
