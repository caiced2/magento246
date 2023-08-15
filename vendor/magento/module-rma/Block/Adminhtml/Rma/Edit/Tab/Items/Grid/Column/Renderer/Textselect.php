<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Grid column widget for rendering action grid cells
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

class Textselect extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders column as select when it is editable
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    protected function _getEditableView(\Magento\Framework\DataObject $row)
    {
        $selectName = 'items[' . $row->getId() . '][' . $this->getColumn()->getId() . ']';
        $isRequired = $this->getColumn()['required'] ?? 'required-entry';
        $html = '<select name="' . $selectName . '" class="admin__control-select ' . $isRequired . ' ">';
        $value = $row->getData($this->getColumn()->getIndex());
        $selected = '';
        if (empty($this->getColumn()->getOptions()[$value])) {
            $selected = ' selected="selected"';
        }
        $html .= "<option value='' {$selected} ></option>";
        foreach ($this->getColumn()->getOptions() as $val => $label) {
            $selected = $val == $value && $value !== null ? ' selected="selected"' : '';
            $html .= '<option value="' . $val . '"' . $selected . '>' . $label . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Renders column as select when it is non-editable
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getNonEditableView(\Magento\Framework\DataObject $row): string
    {
        if (empty($this->getColumn()->getOptions()[$row->getData($this->getColumn()->getIndex())])) {
            $row->setData($this->getColumn()->getIndex(), "");
        }

        return $this->_getValue($row);
    }
}
