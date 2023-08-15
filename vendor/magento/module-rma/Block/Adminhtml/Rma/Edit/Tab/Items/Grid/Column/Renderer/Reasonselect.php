<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

/**
 * Grid column widget for rendering action grid cells
 */
class Reasonselect extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Ram item form
     *
     * @var \Magento\Rma\Model\Item\FormFactory
     */
    protected $_itemFormFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Rma\Model\Item\Status $itemStatus
     * @param \Magento\Rma\Model\Item\FormFactory $itemFormFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Rma\Model\Item\Status $itemStatus,
        \Magento\Rma\Model\Item\FormFactory $itemFormFactory,
        array $data = []
    ) {
        $this->_itemFormFactory = $itemFormFactory;
        parent::__construct($context, $itemStatus, $data);
    }

    /**
     * Renders column as select when it is editable
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    protected function _getEditableView(\Magento\Framework\DataObject $row)
    {
        /** @var $itemForm \Magento\Rma\Model\Item\Form */
        $itemForm = $this->_itemFormFactory->create();
        $rmaItemAttribute = $itemForm->setFormCode('default')->getAttribute('reason_other');

        $selectName = 'items[' . $row->getId() . '][' . $this->getColumn()->getId() . ']';
        $html = '<select name="' .
            $selectName .
            '" class="admin__control-select reason required-entry">' .
            '<option value=""></option>';

        $selectedIndex = $row->getData($this->getColumn()->getIndex());
        foreach ($this->getColumn()->getOptions() as $val => $label) {
            $selected = isset($selectedIndex) && $val == $selectedIndex ? ' selected="selected"' : '';
            $html .= '<option value="' . $val . '"' . $selected . '>' . $label . '</option>';
        }

        if ($rmaItemAttribute && $rmaItemAttribute->getId()) {
            $selected = $selectedIndex == 0 && $row->getReasonOther() != '' ? ' selected="selected"' : '';
            $html .= '<option value="other"' . $selected . '>' . $rmaItemAttribute->getStoreLabel() . '</option>';
        }

        $html .= '</select>';
        $html .= '<input type="text" ' .
            'name="items[' .
            $row->getId() .
            '][reason_other]" ' .
            'value="' .
            $this->escapeHtml(
                $row->getReasonOther()
            ) .
            '" ' .
            'maxlength="255" ' .
            'class="input-text admin__control-text ' .
            $this->getColumn()->getInlineCss() .
            '" ' .
            'style="display:none" />';

        return $html;
    }

    /**
     * Renders column as select when it is not editable
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    protected function _getNonEditableView(\Magento\Framework\DataObject $row)
    {
        /** @var $itemForm \Magento\Rma\Model\Item\Form */
        $itemForm = $this->_itemFormFactory->create();
        $rmaItemAttribute = $itemForm->setFormCode('default')->getAttribute('reason_other');
        $value = $row->getData($this->getColumn()->getIndex());

        if ($value == 0 && $row->getReasonOther() != '') {
            $html = $rmaItemAttribute &&
            $rmaItemAttribute->getId() ? $rmaItemAttribute->getStoreLabel() . ':&nbsp;' : '';

            if (strlen($row->getReasonOther()) > 18) {
                $html .= '<a class="item_reason_other">' . $this->escapeHtml(
                        substr($row->getReasonOther(), 0, 15)
                    ) . '...' . '</a>';

                $html .= '<input type="hidden" ' .
                    'name="items[' .
                    $row->getId() .
                    '][' .
                    $rmaItemAttribute->getAttributeCode() .
                    ']" ' .
                    'value="' .
                    $this->escapeHtml(
                        $row->getReasonOther()
                    ) . '" />';
            } else {
                $html .= $this->escapeHtml($row->getReasonOther());
            }
        } else {
            $value = (empty($this->getColumn()->getOptions()[(int)$value])) ? "" : $this->_getValue($row);
            $html = $this->escapeHtml($value);
        }

        return $html;
    }
}
