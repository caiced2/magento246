<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Observer;

use Magento\Backend\Block\Template;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Backend\Block\Widget\Grid;
use Magento\Framework\Event\ObserverInterface;

class AdminhtmlBlockHtmlBeforeObserver implements ObserverInterface
{
    /**
     * Instance of http request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * Function name and corresponding block names
     *
     * @var array
     */
    protected $_filterRules = [
        '_removeStatusMassaction' => ['product.grid', 'admin.product.grid'],
        '_removeColumnPrice' => [
            'category.product.grid',
            'products',
            'wishlist',
            'compared',
            'rcompared',
            'rviewed',
            'ordered',
            'checkout.accordion.products',
            'checkout.accordion.wishlist',
            'checkout.accordion.compared',
            'checkout.accordion.rcompared',
            'checkout.accordion.rviewed',
            'checkout.accordion.ordered',
            'admin.product.edit.tab.super.config.grid',
            'product.grid',
            'productGrid',
            'admin.product.grid'
        ],
        '_removeColumnsPriceTotal' => ['admin.customer.view.cart'],
        '_setCanReadPriceFalse' => ['checkout.items', 'items'],
        '_setCanEditReadPriceFalse' => [
            'catalog.product.edit.tab.downloadable.links',
            'adminhtml.catalog.product.bundle.edit.tab.attributes.price'
        ],
        '_setOptionsEditReadFalse' => ['admin.product.options'],
        '_setCanEditReadDefaultPrice' => ['adminhtml.catalog.product.bundle.edit.tab.attributes.price'],
        '_setCanEditReadChildBlock' => ['adminhtml.catalog.product.edit.tab.bundle.option'],
    ];

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Store list manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var ObserverData
     */
    protected $observerData;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ObserverData $observerData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ObserverData $observerData,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_request = $request;
        $this->_storeManager = $storeManager;
        $this->observerData = $observerData;

        if (isset($data['can_edit_product_price']) && false === $data['can_edit_product_price']) {
            $this->observerData->setCanEditProductPrice(false);
        }
        if (isset($data['can_read_product_price']) && false === $data['can_read_product_price']) {
            $this->observerData->setCanReadProductPrice(false);
        }
        if (isset($data['can_edit_product_status']) && false === $data['can_edit_product_status']) {
            $this->observerData->setCanEditProductStatus(false);
        }
        if (isset($data['default_product_price_string'])) {
            $this->observerData->setDefaultProductPriceString($data['default_product_price_string']);
        }
    }

    /**
     * Handle adminhtml_block_html_before event
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var $block Template */
        $block = $observer->getBlock();

        $this->_filterByBlockName($block);

        // Handle prices that are shown when admin reviews customers shopping cart
        if (stripos($block->getNameInLayout(), 'customer_cart_') === 0) {
            if (!$this->observerData->isCanReadProductPrice()) {
                if ($block->getParentBlock()->getNameInLayout() == 'admin.customer.carts') {
                    $this->_removeColumnFromGrid($block, 'price');
                    $this->_removeColumnFromGrid($block, 'total');
                }
            }
        }
    }

    /**
     * Call needed function depending on block name
     *
     * @param Template $block
     * @return void
     */
    protected function _filterByBlockName($block)
    {
        $blockName = $block->getNameInLayout();
        foreach ($this->_filterRules as $function => $list) {
            if (in_array($blockName, $list)) {
                call_user_func([$this, $function], $block);
            }
        }
    }

    /**
     * Remove status option in massaction
     *
     * @param Template $block
     * @return void
     */
    protected function _removeStatusMassaction($block)
    {
        if (!$this->observerData->isCanEditProductStatus()) {
            $block->getMassactionBlock()->removeItem('status');
        }
    }

    /**
     * Remove price column from grid
     *
     * @param Template $block
     * @return void
     */
    protected function _removeColumnPrice($block)
    {
        $this->_removeColumnsFromGrid($block, ['price']);
    }

    /**
     * Remove price and total columns from grid
     *
     * @param Template $block
     * @return void
     */
    protected function _removeColumnsPriceTotal($block)
    {
        $this->_removeColumnsFromGrid($block, ['price', 'total']);
    }

    /**
     * Set read price to false
     *
     * @param Template $block
     * @return void
     */
    protected function _setCanReadPriceFalse($block)
    {
        if (!$this->observerData->isCanReadProductPrice()) {
            $block->setCanReadPrice(false);
        }
    }

    /**
     * Set read and edit price to false
     *
     * @param Template $block
     * @return void
     */
    protected function _setCanEditReadPriceFalse($block)
    {
        $this->_setCanReadPriceFalse($block);
        if (!$this->observerData->isCanEditProductPrice()) {
            $block->setCanEditPrice(false);
        }
    }

    /**
     * Set edit and read price in child block to false
     *
     * @param Template $block
     * @return void
     */
    protected function _setOptionsEditReadFalse($block)
    {
        if (!$this->observerData->isCanEditProductPrice()) {
            $optionsBoxBlock = $block->getChildBlock('options_box');
            if ($optionsBoxBlock !== null) {
                $optionsBoxBlock->setCanEditPrice(false);
                if (!$this->observerData->isCanReadProductPrice()) {
                    $optionsBoxBlock->setCanReadPrice(false);
                }
            }
        }
    }

    /**
     * Set default product price
     *
     * @param Template $block
     * @return void
     */
    protected function _setCanEditReadDefaultPrice($block)
    {
        // Handle Price tab of bundle product
        if (!$this->observerData->isCanEditProductPrice()) {
            $block->setDefaultProductPrice($this->observerData->getDefaultProductPriceString());
        }
    }

    /**
     * Set edit and read price to child block
     *
     * @param Template $block
     * @return void
     */
    protected function _setCanEditReadChildBlock($block)
    {
        // Handle selection prices of bundle product with fixed price
        $selectionTemplateBlock = $block->getChildBlock('selection_template');
        if (!$this->observerData->isCanReadProductPrice()) {
            $block->setCanReadPrice(false);
            if ($selectionTemplateBlock !== null) {
                $selectionTemplateBlock->setCanReadPrice(false);
            }
        }
        if (!$this->observerData->isCanEditProductPrice()) {
            $block->setCanEditPrice(false);
            if ($selectionTemplateBlock !== null) {
                $selectionTemplateBlock->setCanEditPrice(false);
            }
        }
    }

    /**
     * Remove columns from grid
     *
     * @param Grid $block
     * @param array $columns
     * @return void
     */
    protected function _removeColumnsFromGrid($block, array $columns)
    {
        if (!$this->observerData->isCanReadProductPrice()) {
            foreach ($columns as $column) {
                $this->_removeColumnFromGrid($block, $column);
            }
        }
    }

    /**
     * Remove column from grid
     *
     * @param Grid $block
     * @param string $column
     * @return Grid|bool
     */
    protected function _removeColumnFromGrid($block, $column)
    {
        if (!$block instanceof \Magento\Backend\Block\Widget\Grid\Extended) {
            return false;
        }
        return $block->removeColumn($column);
    }
}
