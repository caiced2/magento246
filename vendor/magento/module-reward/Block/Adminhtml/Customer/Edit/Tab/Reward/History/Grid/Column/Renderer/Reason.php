<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Column renderer for messages in reward history grid
 *
 */
namespace Magento\Reward\Block\Adminhtml\Customer\Edit\Tab\Reward\History\Grid\Column\Renderer;

class Reason extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Render "Expired / not expired" reward "Reason" field
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    protected function _getValue(\Magento\Framework\DataObject $row)
    {
        $expired = '';
        if ($row->getData('is_duplicate_of') !== null) {
            $expired = '<em>' . __('Expired reward') . '</em> ';
        }
        return $expired . parent::_getValue($row);
    }
}
