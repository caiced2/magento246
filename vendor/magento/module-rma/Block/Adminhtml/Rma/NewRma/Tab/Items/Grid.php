<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items;

use Magento\Catalog\Model\Product;

/**
 * Admin RMA create order grid block
 *
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
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
     * Rma item collection
     *
     * @var \Magento\Rma\Model\ResourceModel\Item\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Rma\Model\Item
     */
    protected $rmaItem;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory
     * @param \Magento\Rma\Helper\Eav $rmaEav
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Rma\Model\Item $rmaItem
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Rma\Model\ResourceModel\Item\CollectionFactory $collectionFactory,
        \Magento\Rma\Helper\Eav $rmaEav,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Rma\Model\Item $rmaItem,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_rmaEav = $rmaEav;
        $this->rmaItem = $rmaItem;
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
        $this->setId('rma_items_grid');
        $this->setDefaultSort('entity_id');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
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
        /** @var $collection \Magento\Rma\Model\ResourceModel\Item\Collection */
        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addAttributeToFilter('entity_id', null);

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
        $this->addColumn(
            'product_name',
            [
                'header' => __('Product'),
                'type' => 'text',
                'index' => 'product_name',
                'sortable' => false,
                'escape' => true,
                'header_css_class' => 'col-product required',
                'column_css_class' => 'col-product'
            ]
        );

        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'type' => 'text',
                'index' => 'product_sku',
                'sortable' => false,
                'escape' => true,
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
                'type' => 'text',
                'index' => 'qty_ordered',
                'sortable' => false,
                'order_data' => $this->getOrderItemsData(),
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Quantity::class,
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty'
            ]
        );

        $this->addColumn(
            'qty_requested',
            [
                'header' => __('Requested'),
                'index' => 'qty_requested',
                'type' => 'input',
                'sortable' => false,
                'header_css_class' => 'col-qty required',
                'column_css_class' => 'col-qty'
            ]
        );

        $eavHelper = $this->_rmaEav;
        $this->addColumn(
            'reason',
            [
                'header' => __('Return Reason'),
                'getter' => [$this, 'getReasonOptionStringValue'],
                'type' => 'select',
                'options' => ['' => ''] + $eavHelper->getAttributeOptionValues('reason'),
                'index' => 'reason',
                'sortable' => false,
                'header_css_class' => $eavHelper->isRequiredAttribute('reason') ? 'col-reason required' : 'col-reason',
                'column_css_class' => 'col-reason'
            ]
        );

        $this->addColumn(
            'condition',
            [
                'header' => __('Item Condition'),
                'type' => 'select',
                'options' => ['' => ''] + $eavHelper->getAttributeOptionValues('condition'),
                'index' => 'condition',
                'sortable' => false,
                'header_css_class' => $eavHelper->isRequiredAttribute('condition')
                    ? 'col-condition required' : 'col-condition',
                'column_css_class' => 'col-condition'
            ]
        );

        $this->addColumn(
            'resolution',
            [
                'header' => __('Resolution'),
                'index' => 'resolution',
                'type' => 'select',
                'options' => ['' => ''] + $eavHelper->getAttributeOptionValues('resolution'),
                'sortable' => false,
                'header_css_class' => $eavHelper->isRequiredAttribute('resolution')
                    ? 'col-resolution required' : 'col-resolution',
                'column_css_class' => 'col-resolution'
            ]
        );

        $actionsArray = [
            [
                'caption' => __('Delete'),
                'url' => ['base' => '*/*/delete'],
                'field' => 'id',
                'onclick' => 'alert(\'Delete\');return false;',
            ],
            [
                'caption' => __('Add Details'),
                'url' => ['base' => '*/*/edit'],
                'field' => 'id',
                'onclick' => 'alert(\'Details\');return false;'
            ],
        ];

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Action::class,
                'actions' => $actionsArray,
                'sortable' => false,
                'is_system' => true,
                'header_css_class' => 'col-actions',
                'column_css_class' => 'col-actions'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get available for return item quantity
     *
     * @param \Magento\Framework\DataObject $row
     * @return float
     */
    public function getRemainingQty($row)
    {
        return $this->rmaItem->getReturnableQty($row->getOrderId(), $row->getId());
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
     * Return row url for js event handlers
     *
     * @param Product|\Magento\Framework\DataObject $item
     * @return string|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRowUrl($item)
    {
        //$res = parent::getRowUrl($item);
        return null;
    }
}
