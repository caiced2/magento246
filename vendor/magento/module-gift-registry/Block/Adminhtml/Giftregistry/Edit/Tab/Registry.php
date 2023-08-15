<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Tab;

/**
 * @codeCoverageIgnore
 */
class Registry extends \Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Attribute\Attribute
{
    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setFormTitle(__('Attributes'));
    }

    /**
     * Get field prefix
     *
     * @return string
     */
    public function getFieldPrefix()
    {
        return 'registry';
    }
}
