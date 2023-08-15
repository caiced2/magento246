<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml customer orders grid action column item renderer
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block\Adminhtml\Customer\Edit\Tab\Renderer;

class Action extends \Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action
{
    /**
     * Render field HRML for column
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $actions = [];
        if ($row->getIsReturnable()) {
            $actions[] = [
                '@' => ['href' => $this->getUrl('adminhtml/rma/new', ['order_id' => $row->getId()])],
                '#' => __('Return'),
            ];
        }
        $link1 = parent::render($row);
        $link2 = $this->_actionsToHtml($actions);
        $separator = $link1 && $link2 ? '<span class="separator">|</span>' : '';
        return $link1 . $separator . $link2;
    }
}
