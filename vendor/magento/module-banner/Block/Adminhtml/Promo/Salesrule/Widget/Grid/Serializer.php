<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Block\Adminhtml\Promo\Salesrule\Widget\Grid;

class Serializer extends \Magento\Backend\Block\Widget\Grid\Serializer
{
    /**
     * {@inheritDoc}
     * TODO: remove after MAGETWO-48080 (ui_component grid on catalogRule page)
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Magento_Banner::promo/salesrule/widget/grid/serializer.phtml');
    }
}
