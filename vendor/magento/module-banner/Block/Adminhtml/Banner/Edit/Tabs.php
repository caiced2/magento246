<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Banner\Edit;

/**
 * @api
 * @since 100.0.2
 * @deprecated Banner form configuration has been moved on ui component declaration
 * @see app/code/Magento/Banner/view/adminhtml/ui_component/banner_form.xml
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Initialize banner edit page tabs
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('banner_info_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Dynamic Block Information'));
    }
}
