<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping;

/**
 * Shipment Information block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Information extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shipping\Packaging
{
    /**
     * Constructor
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('edit/shipping/information.phtml');
    }
}
