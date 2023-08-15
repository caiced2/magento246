<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Adminhtml\Giftregistry\Edit\Attribute\Type;

/**
 * @codeCoverageIgnore
 */
class Select extends \Magento\Backend\Block\Widget\Form
{
    /**
     * @var string
     */
    protected $_template = 'edit/type/select.phtml';

    /**
     * Preparing block layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_select_row_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Add New Option'),
                'class' => 'add add-select-row',
                'id' => '<%- data.prefix %>_add_select_row_button_<%- data.id %>'
            ]
        );

        $this->addChild(
            'delete_select_row_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Delete Option'), 'class' => 'delete delete-select-row icon-btn']
        );

        return parent::_prepareLayout();
    }

    /**
     * Retrieve add button html
     *
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_select_row_button');
    }

    /**
     * Retrieve delete button html
     *
     * @return string
     */
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_select_row_button');
    }
}
