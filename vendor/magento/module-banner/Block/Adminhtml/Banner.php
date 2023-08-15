<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml;

/**
 * @api
 * @since 100.0.2
 */
class Banner extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize banners manage page
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_banner';
        $this->_blockGroup = 'Magento_Banner';
        $this->_headerText = __('Dynamic Blocks');
        $this->_addButtonLabel = __('Add Dynamic Block');
        parent::_construct();
    }
}
