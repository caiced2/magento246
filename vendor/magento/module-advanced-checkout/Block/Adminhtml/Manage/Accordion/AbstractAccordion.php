<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Abstract class for accordion grids
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
abstract class AbstractAccordion extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Collection field name for using in controls
     * @var string
     */
    protected $_controlFieldName = 'entity_id';

    /**
     * Javascript list type name for this grid
     *
     * @var string
     */
    protected $_listType = 'product_to_add';

    /**
     * Url to configure this grid's items
     *
     * @var string
     */
    protected $_configureRoute = 'checkout/index/configureProductToAdd';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Data\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize Grid
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setUseAjax(true);
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
        $this->setRowClickCallback('checkoutObj.productGridRowClick.bind(checkoutObj)');
        $this->setCheckboxCheckCallback('checkoutObj.productGridCheckboxCheck.bind(checkoutObj)');
        $this->setRowInitCallback('checkoutObj.productGridRowInit.bind(checkoutObj)');
        $this->setRequireJsDependencies(['Magento_AdvancedCheckout/shopping_cart']);
    }

    /**
     * Workaround for displaying empty grid when no items found
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsCollapsed()
    {
        return $this->getItemsCount() == 0;
    }

    /**
     * Return items count
     *
     * @return int
     */
    public function getItemsCount()
    {
        $collection = $this->getItemsCollection();
        if ($collection) {
            return count($collection->getItems());
        }
        return 0;
    }

    /**
     * Return items collection
     *
     * @codeCoverageIgnore
     * @return \Magento\Framework\Data\Collection
     */
    public function getItemsCollection()
    {
        return $this->_collectionFactory->create();
    }

    /**
     * Prepare collection for grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->setCollection($this->getItemsCollection());
        return parent::_prepareCollection();
    }

    /**
     * Returns special renderer for price column content
     *
     * @codeCoverageIgnore
     * @return null
     */
    protected function _getPriceRenderer()
    {
        return null;
    }

    /**
     * Prepare Grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_name',
            [
                'header' => __('Product'),
                'renderer' => \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Product::class,
                'index' => 'name',
                'sortable' => false
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'renderer' => $this->_getPriceRenderer(),
                'align' => 'right',
                'type' => 'currency',
                'column_css_class' => 'price',
                'currency_code' => $this->_getStore()->getCurrentCurrencyCode(),
                'rate' => $this->_getStore()->getBaseCurrency()->getRate($this->_getStore()->getCurrentCurrencyCode()),
                'index' => 'price',
                'sortable' => false
            ]
        );

        $this->_addControlColumns();

        return parent::_prepareColumns();
    }

    /**
     * Add columns with controls to manage added products and their quantity
     *
     * @return $this
     */
    protected function _addControlColumns()
    {
        $this->addColumn(
            'in_products',
            [
                'type' => 'checkbox',
                'field_name' => $this->getId() ? $this->getId() : 'source_product',
                'index' => $this->_controlFieldName,
                'use_index' => true,
                'sortable' => false
            ]
        );

        $this->addColumn(
            'qty',
            [
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer\Qty::class,
                'name' => 'qty',
                'inline_css' => 'qty',
                'align' => 'right',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty',
                'width' => '1'
            ]
        );

        return $this;
    }

    /**
     * Return current customer from registry
     *
     * @codeCoverageIgnore
     * @return \Magento\Customer\Model\Customer
     */
    protected function _getCustomer()
    {
        return $this->_coreRegistry->registry('checkout_current_customer');
    }

    /**
     * Return current store from registry
     *
     * @return \Magento\Store\Model\Store
     * @codeCoverageIgnore
     */
    protected function _getStore()
    {
        return $this->_coreRegistry->registry('checkout_current_store');
    }

    /**
     * Returns javascript list type of this grid
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getListType()
    {
        return $this->_listType;
    }

    /**
     * Returns url to configure item
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getConfigureUrl()
    {
        $params = ['customer' => $this->_getCustomer()->getId(), 'store' => $this->_getStore()->getId()];
        return $this->getUrl($this->_configureRoute, $params);
    }

    /**
     * Returns additional javascript to init this grid
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getAdditionalJavaScript()
    {
        $productConfigure = "productConfigure.addListType('" .
            $this->getListType() .
            "', {urlFetch: '" .
            $this->getConfigureUrl() .
            "'})\n";
        $checkoutObj = "checkoutObj.addSourceGrid({htmlId: '" .
            $this->getId() .
            "', listType: '" .
            $this->getListType() .
            "'});\n";

        return "if (jQuery(document).data('productConfigureInited')) {\n" .
            $productConfigure .
            "} else {\n" .
            "jQuery(document).on('productConfigure:inited', function () {" .
            $productConfigure .
            "});\n" .
            "}" .
            "if (jQuery(document).data('adminCheckoutInited')) {\n" .
            $checkoutObj .
            "} else {\n" .
            "jQuery(document).on('adminCheckout:inited', function () {" .
            $checkoutObj .
            "});\n" .
            "}";
    }
}
