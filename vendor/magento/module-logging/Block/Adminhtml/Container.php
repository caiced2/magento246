<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Block\Adminhtml;

/**
 * General Logging container
 *
 * @api
 * @since 100.0.2
 */
class Container extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Remove add button
     * Set block group and controller
     *
     * @return void
     */
    protected function _construct()
    {
        $action = $this->_request->getActionName();
        $this->_blockGroup = 'Magento_Logging';
        $this->_controller = 'adminhtml_' . $action;

        parent::_construct();
        $this->buttonList->remove('add');
    }

    /**
     * Header text getter
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __($this->getData('header_text'));
    }
}
