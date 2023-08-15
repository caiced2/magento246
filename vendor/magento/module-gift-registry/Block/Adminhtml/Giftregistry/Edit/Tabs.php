<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit;

/**
 * @codeCoverageIgnore
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Initialize form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('magento_giftregistry_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Gift Registry'));
    }

    /**
     * Add tab sections
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $this->addTab(
            'general_section',
            [
                'label' => __('General Information'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Tab\General::class
                )->toHtml()
            ]
        );

        $this->addTab(
            'registry_attributes',
            [
                'label' => __('Attributes'),
                'content' => $this->getLayout()->createBlock(
                    \Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Tab\Registry::class
                )->toHtml()
            ]
        );

        return parent::_beforeToHtml();
    }
}
