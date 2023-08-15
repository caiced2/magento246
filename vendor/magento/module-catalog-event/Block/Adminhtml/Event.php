<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Block\Adminhtml;

use Magento\Backend\Block\Widget\Grid\Container;

/**
 * Catalog Events Adminhtml Block
 * @api
 * @api
 * @since 100.0.2
 */
class Event extends Container
{
    /**
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_controller = 'adminhtml_event';
        $this->_blockGroup = 'Magento_CatalogEvent';
        $this->_headerText = __('Events');
        $this->_addButtonLabel = __('Add Catalog Event');
        parent::_construct();
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getHeaderCssClass()
    {
        return 'icon-head head-catalogevent';
    }
}
