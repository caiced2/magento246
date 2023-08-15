<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Gift Wrapping Adminhtml Block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @codeCoverageIgnore
 */
namespace Magento\GiftWrapping\Block\Adminhtml;

/**
 * @api
 * @since 100.0.2
 */
class Giftwrapping extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize gift wrapping management page
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_giftwrapping';
        $this->_blockGroup = 'Magento_GiftWrapping';
        $this->_headerText = __('Gift Wrapping');
        $this->_addButtonLabel = __('Add Gift Wrapping');
        parent::_construct();
    }
}
