<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Currency;

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
     * Default limit for order item collection
     *
     * We cannot manage items quantity in right way so we get all the items without limits and paging
     *
     * @var int
     */
    protected $_defaultLimit = 0;

    /**
     * Rma data
     *
     * @var \Magento\Rma\Helper\Data
     */
    protected $_rmaData;

    /**
     * Registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Rma item factory
     *
     * @var \Magento\Rma\Model\ResourceModel\ItemFactory
     */
    protected $_itemFactory;

    /**
     * Catalog product model
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Sales\Model\Order\Admin\Item
     */
    protected $adminOrderItem;

    /**
     * @var \Magento\Rma\Model\Item
     */
    protected $rmaItem;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Rma\Helper\Data $rmaData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Order\Admin\Item $adminOrderItem
     * @param \Magento\Rma\Model\Item $rmaItem
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Rma\Model\ResourceModel\ItemFactory $itemFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Rma\Helper\Data $rmaData,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\Order\Admin\Item $adminOrderItem,
        \Magento\Rma\Model\Item $rmaItem,
        array $data = []
    ) {
        $this->_itemFactory = $itemFactory;
        $this->_productFactory = $productFactory;
        $this->_rmaData = $rmaData;
        $this->registry = $registry;
        $this->adminOrderItem = $adminOrderItem;
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
        $this->setId('order_items_grid');
        $this->setDefaultSort('item_id');
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
    }

    /**
     * Prepare grid collection object
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $orderId = $this->registry->registry('current_order')->getId();
        /** @var $resourceItem \Magento\Rma\Model\ResourceModel\Item */
        $resourceItem = $this->_itemFactory->create();
        $orderItemsCollection = $resourceItem->getOrderItemsCollection($orderId);
        $this->setCollection($orderItemsCollection);
        return parent::_prepareCollection();
    }

    /**
     * After load collection processing.
     *
     * Filter items collection due to RMA needs. Remove forbidden items
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _afterLoadCollection()
    {
        $orderId = $this->registry->registry('current_order')->getId();
        /** @var $resourceItem \Magento\Rma\Model\ResourceModel\Item */
        $resourceItem = $this->_itemFactory->create();
        $returnableItems = $resourceItem->getReturnableItems($orderId);

        /** @var $resourceItem \Magento\Rma\Model\ResourceModel\Item */
        $resourceItem = $this->_itemFactory->create();
        $fullItemsCollection = $resourceItem->getOrderItemsCollection($orderId);
        /**
         * contains data that defines possibility of return for an order item
         * array value ['self'] refers to item's own rules
         * array value ['child'] refers to rules defined from item's sub-items
         */
        $parent = [];

        /** @var $product \Magento\Catalog\Model\Product */
        $product = $this->_productFactory->create();

        foreach ($fullItemsCollection as $item) {
            $allowed = isset($returnableItems[$item->getId()]);

            if ($allowed === true) {
                $product->reset();
                $product->setStoreId($item->getStoreId());
                try {
                    $product->load($this->adminOrderItem->getProductId($item));
                } catch (LocalizedException $e) {
                    $allowed = false;
                }

                if (!$this->_rmaData->canReturnProduct($product, $item->getStoreId())) {
                    $allowed = false;
                }
            }

            if ($item->getParentItemId()) {
                if (!isset($parent[$item->getParentItemId()]['child'])) {
                    $parent[$item->getParentItemId()]['child'] = false;
                }
                $parent[$item->getParentItemId()]['child'] = $parent[$item->getParentItemId()]['child'] || $allowed;
                $parent[$item->getItemId()]['self'] = false;
            } else {
                $parent[$item->getItemId()]['self'] = $allowed;
            }
        }

        foreach ($this->getCollection() as $item) {
            if (isset($parent[$item->getId()]['self']) && $parent[$item->getId()]['self'] === false) {
                $this->getCollection()->removeItemByKey($item->getId());
                continue;
            }
            if (isset($parent[$item->getId()]['child']) && $parent[$item->getId()]['child'] === false) {
                $this->getCollection()->removeItemByKey($item->getId());
                continue;
            }

            $item->setName($this->_rmaData->getAdminProductName($item));
        }

        return $this;
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'select',
            [
                'header' => __('Select'),
                'type' => 'checkbox',
                'align' => 'center',
                'sortable' => false,
                'index' => 'item_id',
                'values' => $this->_getSelectedProducts(),
                'name' => 'in_products',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'product_name',
            [
                'header' => __('Product'),
                'renderer' => \Magento\Rma\Block\Adminhtml\Product\Bundle\Product::class,
                'index' => 'name',
                'escape' => true,
                'header_css_class' => 'col-product col-rma-product',
                'column_css_class' => 'col-product col-rma-product'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'type' => 'text',
                'index' => 'sku',
                'escape' => true,
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'renderer' => Currency::class,
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );

        $this->addColumn(
            'available_qty',
            [
                'header' => __('Remaining'),
                'type' => 'text',
                'getter' => [$this, 'getRemainingQty'],
                'renderer' => \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Grid\Column\Renderer\Quantity::class,
                'index' => 'available_qty',
                'header_css_class' => 'col-qty',
                'column_css_class' => 'col-qty',
                'filter' => false,
                'sortable' => false,
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
     * Grid Row JS Callback
     *
     * @return string
     */
    public function getRowClickCallback()
    {
        $js = '
            function (grid, event) {
                return rma.addProductRowCallback(grid, event);
            }
        ';
        return $js;
    }

    /**
     * Checkbox Click JS Callback
     *
     * @return string
     */
    public function getCheckboxCheckCallback()
    {
        $js = '
            function (grid, element, checked) {
                return rma.addProductCheckboxCheckCallback(grid, element, checked);
            }
        ';
        return $js;
    }

    /**
     * Get Url to action to reload grid
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/*/addProductGrid', ['_current' => true]);
    }

    /**
     * List of selected products
     *
     * @return int[]
     * @SuppressWarnings(PHPMD.RequestAwareBlockMethod)
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('products', []);

        if (!is_array($products)) {
            $products = [];
        } else {
            foreach ($products as &$value) {
                $value = (int)$value;
            }
        }

        return $products;
    }

    /**
     * Setting column filters to collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for selected products flag
        if ($column->getId() == 'select') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('item_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('item_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
}
