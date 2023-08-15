<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer;

/**
 * Grid column widget for rendering action grid cells depending on item status
 */
class AbstractRenderer extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Rma item status
     *
     * @var \Magento\Rma\Model\Item\Status
     */
    protected $_itemStatus;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Rma\Model\Item\Status $itemStatus
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Rma\Model\Item\Status $itemStatus,
        array $data = []
    ) {
        $this->_itemStatus = $itemStatus;
        parent::__construct($context, $data);
    }

    /**
     * Renders column
     *
     * Render column depending on row status value, which define whether cell is editable
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $this->_itemStatus->setStatus($row->getStatus());
        $this->setStatusManager($this->_itemStatus);

        if ($this->_itemStatus->getAttributeIsEditable($this->getColumn()->getIndex())) {
            return $this->_getEditableView($row);
        } else {
            return $this->_getNonEditableView($row);
        }
    }

    /**
     * Render method when attribute is editable
     *
     * Must be overwritten in child classes
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getEditableView(\Magento\Framework\DataObject $row)
    {
        return parent::render($row);
    }

    /**
     * Render method when attribute is not editable
     *
     * Must be overwritten in child classes
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    protected function _getNonEditableView(\Magento\Framework\DataObject $row)
    {
        return parent::render($row);
    }
}
