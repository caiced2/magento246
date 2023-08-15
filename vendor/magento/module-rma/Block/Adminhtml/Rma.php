<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * RMA Adminhtml Block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block\Adminhtml;

/**
 * @api
 * @since 100.0.2
 */
class Rma extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Initialize RMA management page
     *
     * @return void
     */
    public function _construct()
    {
        $this->_controller = 'adminhtml_rma';
        $this->_blockGroup = 'Magento_Rma';
        $this->_headerText = __('Returns');
        $this->_addButtonLabel = __('New Return Request');

        parent::_construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareLayout()
    {
        $parent = $this->getParentBlock();
        if ($parent instanceof Customer\Edit\Tab\Rma || $parent instanceof Order\View\Tab\Rma) {
            $this->removeButton('add');
        }
        return parent::_prepareLayout();
    }

    /**
     * Get URL for New RMA Button
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('adminhtml/*/new');
    }
}
