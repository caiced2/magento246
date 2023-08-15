<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Grid column widget for rendering cells, which can be of text or select type
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

class Textinput extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Renders quantity as integer
     *
     * @param \Magento\Framework\DataObject $row
     * @return int|string
     */
    public function _getValue(\Magento\Framework\DataObject $row)
    {
        $quantity = parent::_getValue($row);
        if (!$row->getIsQtyDecimal()) {
            $quantity = intval($quantity);
        }
        return $quantity;
    }

    /**
     * Renders column as input when it is editable
     *
     * @param   \Magento\Framework\DataObject $row
     * @return  string
     */
    protected function _getEditableView(\Magento\Framework\DataObject $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        if (!$row->getIsQtyDecimal() && $value !== null) {
            $value = intval($value);
        }
        $class = 'input-text admin__control-text ' . $this->getColumn()->getValidateClass();
        $html = '<input type="text" ';
        $html .= 'name="items[' . $row->getId() . '][' . $this->getColumn()->getId() . ']" ';
        $html .= 'value="' . $value . '" ';
        if ($this->getStatusManager()->getAttributeIsDisabled($this->getColumn()->getId())) {
            $html .= ' disabled="disabled" ';
            $class .= ' disabled ';
        }
        $html .= 'class="' . $class . '" />';
        return $html;
    }
}
