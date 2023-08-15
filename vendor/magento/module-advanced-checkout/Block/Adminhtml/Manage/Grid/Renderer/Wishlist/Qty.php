<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Adminhtml grid product qty column renderer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Wishlist;

class Qty extends \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Qty
{
    /**
     * Returns whether this qty field must be inactive
     *
     * @codeCoverageIgnore
     * @param   \Magento\Framework\DataObject $row
     * @return  bool
     */
    protected function _isInactive($row)
    {
        return parent::_isInactive($row->getProduct());
    }
}
