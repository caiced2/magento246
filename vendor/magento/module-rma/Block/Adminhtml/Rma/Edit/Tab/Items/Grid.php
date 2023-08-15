<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

/**
 * Admin RMA create order grid block
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Default limit collection
     *
     * @var int
     */
    protected $_defaultLimit = 0;

    /**
     * Variable to store store-depended string values of attributes
     *
     * @var null|array
     */
    protected $_attributeOptionValues = null;

    /**
     * Rma eav
     *
     * @var \Magento\Rma\Helper\Eav
     */
    protected $_rmaEav;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Rma item status
     *
     * @var \Magento\Rma\Model\Item\Status
     */
    protected $_itemStatus;

    /**
     * Attributes for rma items
     *
     * @var \Magento\Rma\Model\ResourceModel\Item\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * Array of all attributes for rma
     *
     * @var array
     */
    protected $attributesCollection;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Rma\Model\Item\Status $itemStatus
     * @param \Magento\Rma\Model\ResourceModel\Item\Attribute\Collection $attributeCollection,
     * @param \Magento\Rma\Helper\Eav $rmaEav
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Rma\Model\Item\Status $itemStatus,
        \Magento\Rma\Model\ResourceModel\Item\Attribute\Collection $attributeCollection,
        \Magento\Rma\Helper\Eav $rmaEav,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_rmaEav = $rmaEav;
        $this->_itemStatus = $itemStatus;
        $this->attributeCollection = $attributeCollection;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Block constructor
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('magento_rma_item_edit_grid');
        $this->setDefaultSort('entity_id');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setSortable(false);
        $this->_gatherOrderItemsData();
    }

    /**
     * Gather items quantity data from Order item collection
     *
     * @return void
     */
    protected function _gatherOrderItemsData()
    {
        $itemsData = [];
        if ($this->_coreRegistry->registry('current_order')) {
            foreach ($this->_coreRegistry->registry('current_order')->getItemsCollection() as $item) {
                $itemsData[$item->getId()] = [
                    'qty_shipped' => $item->getQtyShipped(),
                    'qty_returned' => $item->getQtyReturned(),
                ];
            }
        }
        $this->setOrderItemsData($itemsData);
    }

    /**
     * Prepare grid collection object
     *
     * @return \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid
     */
    protected function _prepareCollection()
    {
        $rma = $this->_coreRegistry->registry('current_rma');

        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $rma->getItemsForDisplay();

        if ($this->getItemFilter()) {
            $collection->addFilter('entity_id', $this->getItemFilter());
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $rma = $this->_coreRegistry->registry('current_rma');
        if ($rma && ($rma->getStatus() === \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED ||
                $rma->getStatus() === \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED)
        ) {
            $this->_itemStatus->setOrderIsClosed();
        }

        $this->addColumn(
            'product_admin_name',
            [
                'header' => __('Product'),
                'type' => 'text',
                'index' => 'product_admin_name',
                'escape' => true,
                'header_css_class' => 'col-product',
                'column_css_class' => 'col-product'
            ]
        );

        $this->addColumn(
            'product_admin_sku',
            [
                'header' => __('SKU'),
                'type' => 'text',
                'index' => 'product_admin_sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );

        //Renderer puts available quantity instead of order_item_id
        $this->addColumn(
            'qty_ordered',
            [
                'header' => __('Remaining'),
                'getter' => [$this, 'getRemainingQty'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Quantity::class,
                'index' => 'qty_ordered',
                'order_data' => $this->getOrderItemsData(),
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'qty_requested',
            [
                'header' => __('Requested'),
                'index' => 'qty_requested',
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textinput::class,
                'validate_class' => 'validate-greater-than-zero',
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'qty_authorized',
            [
                'header' => __('Authorized'),
                'index' => 'qty_authorized',
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textinput::class,
                'validate_class' => 'validate-greater-than-zero',
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'qty_returned',
            [
                'header' => __('Returned'),
                'index' => 'qty_returned',
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textinput::class,
                'validate_class' => 'validate-greater-than-zero',
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'qty_approved',
            [
                'header' => __('Approved'),
                'index' => 'qty_approved',
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textinput::class,
                'validate_class' => 'validate-greater-than-zero',
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'reason',
            [
                'header' => __('Return Reason'),
                'getter' => [$this, 'getReasonOptionStringValue'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Reasonselect::class,
                'options' => $this->_rmaEav->getAttributeOptionValues('reason'),
                'required' => $this->_rmaEav->isRequiredAttribute('resolution'),
                'index' => 'reason',
                'header_css_class' => 'col-reason',
                'column_css_class' => 'col-reason'
            ]
        );

        $this->addColumn(
            'condition',
            [
                'header' => __('Item Condition'),
                'getter' => [$this, 'getConditionOptionStringValue'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textselect::class,
                'options' => $this->_rmaEav->getAttributeOptionValues('condition'),
                'required' => $this->_rmaEav->isRequiredAttribute('resolution'),
                'index' => 'condition',
                'header_css_class' => 'col-condition',
                'column_css_class' => 'col-condition'
            ]
        );

        $this->addColumn(
            'resolution',
            [
                'header' => __('Resolution'),
                'index' => 'resolution',
                'getter' => [$this, 'getResolutionOptionStringValue'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Textselect::class,
                'options' => $this->_rmaEav->getAttributeOptionValues('resolution'),
                'required' => $this->_rmaEav->isRequiredAttribute('resolution'),
                'header_css_class' => 'col-resolution',
                'column_css_class' => 'col-resolution'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'getter' => [$this, 'getStatusOptionStringValue'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Status::class,
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        if (!$this->hasUserDefinedAttributes()) {
            $actionsArray = [
                [
                    'caption' => __('Details'),
                    'class' => 'action action-item-details disabled _disabled',
                    'disabled' => 'disabled',
                ],
            ];
        } else {
            $actionsArray = [['caption' => __('Details'), 'class' => 'action action-item-details']];
        }
        if (!($rma && ($rma->getStatus() === \Magento\Rma\Model\Rma\Source\Status::STATE_CLOSED ||
                $rma->getStatus() === \Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED))
        ) {
            $actionsArray[] = [
                'caption' => __('Split'),
                'class' => 'action action-item-split-line',
                'status_depended' => '1'
            ];
        }

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action::class,
                'actions' => $actionsArray,
                'is_system' => true,
                'header_css_class' => 'col-actions',
                'column_css_class' => 'col-actions'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Check if there exist user-defined attribute for rma in system
     *
     * @return bool
     */
    protected function hasUserDefinedAttributes()
    {
        $count = 0;
        if (!isset($this->attributesCollection)) {
            $this->attributesCollection = $this->attributeCollection->load();
        }
        foreach ($this->attributesCollection as $attribute) {
            if ($attribute->getIsUserDefined()) {
                $count++;
            }
        }
        return (bool)$count;
    }

    /**
     * Get available for return item quantity
     *
     * @param \Magento\Framework\DataObject $row
     * @return float
     */
    public function getRemainingQty($row)
    {
        return $row->getReturnableQty();
    }

    /**
     * Get string value of "Reason to Return" Attribute
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getReasonOptionStringValue($row)
    {
        return $this->_getAttributeOptionStringValue($row->getReason());
    }

    /**
     * Get string value of "Reason to Return" Attribute
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getResolutionOptionStringValue($row)
    {
        return $this->_getAttributeOptionStringValue($row->getResolution());
    }

    /**
     * Get string value of "Reason to Return" Attribute
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getConditionOptionStringValue($row)
    {
        return $this->_getAttributeOptionStringValue($row->getCondition());
    }

    /**
     * Get string value of "Status" Attribute
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function getStatusOptionStringValue($row)
    {
        return $row->getStatusLabel();
    }

    /**
     * Get string value option-type attribute by it's unique int value
     *
     * @param int $value
     * @return string
     */
    protected function _getAttributeOptionStringValue($value)
    {
        if ($this->_attributeOptionValues === null) {
            $this->_attributeOptionValues = $this->_rmaEav->getAttributeOptionStringValues();
        }
        if (isset($this->_attributeOptionValues[$value])) {
            return $this->escapeHtml($this->_attributeOptionValues[$value]);
        } else {
            return $this->escapeHtml($value);
        }
    }

    /**
     * Sets all available fields in editable state
     *
     * @return $this
     */
    public function setAllFieldsEditable()
    {
        $this->_itemStatus->setAllEditable();
        return $this;
    }
}
