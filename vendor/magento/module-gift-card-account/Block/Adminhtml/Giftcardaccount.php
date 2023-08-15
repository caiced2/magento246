<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Block\Adminhtml;

/**
 * @api
 * @since 100.0.2
 */
class Giftcardaccount extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_giftcardaccount';
        $this->_blockGroup = 'Magento_GiftCardAccount';
        $this->_headerText = __('Gift Card Accounts');
        $this->_addButtonLabel = __('Add Gift Card Account');
        parent::_construct();
    }
}
