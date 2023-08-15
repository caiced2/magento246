<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;

/**
 * Accordion grid for recently ordered products
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Ordered extends AbstractAccordion
{
    /**
     * Collection field name for using in controls
     * @var string
     */
    protected $_controlFieldName = 'item_id';

    /**
     * Javascript list type name for this grid
     *
     * @var string
     */
    protected $_listType = 'ordered';

    /**
     * Url to configure this grid's items
     *
     * @var string
     */
    protected $_configureRoute = '*/index/configureOrderedItem';

    /**
     * @var \Magento\Sales\Model\Config
     */
    protected $_salesConfig;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_ordersFactory;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\CatalogInventory\Helper\Stock $stockHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $ordersFactory
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $ordersFactory,
        \Magento\Sales\Model\Config $salesConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_catalogConfig = $catalogConfig;
        $this->stockHelper = $stockHelper;
        $this->_ordersFactory = $ordersFactory;
        $this->_salesConfig = $salesConfig;
        $this->_productFactory = $productFactory;
        parent::__construct($context, $backendHelper, $collectionFactory, $coreRegistry, $data);
    }

    /**
     * Initialize Grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('source_ordered');
        if ($this->_getStore()) {
            $this->setHeaderText(__('Last ordered items (%1)', $this->getItemsCount()));
        }
    }

    /**
     * Returns custom last ordered products renderer for price column content
     *
     * @codeCoverageIgnore
     * @return string
     */
    protected function _getPriceRenderer()
    {
        return \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Grid\Renderer\Ordered\Price::class;
    }

    /**
     * Prepare customer wishlist product collection
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getItemsCollection()
    {
        if (!$this->hasData('items_collection')) {
            $productIds = [];
            $storeIds = $this->_getStore()->getWebsite()->getStoreIds();

            // Load last order of a customer
            /* @var $collection \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
            $collection = $this->_ordersFactory->create()->addAttributeToFilter(
                'customer_id',
                $this->_getCustomer()->getId()
            )->addAttributeToFilter(
                'store_id',
                ['in' => $storeIds]
            )->addAttributeToSort(
                'created_at',
                'desc'
            )->setPage(
                1,
                1
            )->load();
            foreach ($collection as $order) {
                break;
            }

            // Add products to order items
            if (isset($order)) {
                $productIds = [];
                $collection = $order->getItemsCollection();
                foreach ($collection as $item) {
                    if ($item->getParentItem()) {
                        $collection->removeItemByKey($item->getId());
                    } else {
                        $productIds[$item->getProductId()] = $item->getProductId();
                    }
                }
                if ($productIds) {
                    // Load products collection
                    $attributes = $this->_catalogConfig->getProductAttributes();
                    $products = $this->_productFactory->create()->getCollection()->setStore(
                        $this->_getStore()
                    )->addAttributeToSelect(
                        $attributes
                    )->addAttributeToSelect(
                        'sku'
                    )->addAttributeToFilter(
                        'type_id',
                        $this->_salesConfig->getAvailableProductTypes()
                    )->addAttributeToFilter(
                        'status',
                        ProductStatus::STATUS_ENABLED
                    )->addStoreFilter(
                        $this->_getStore()
                    )->addIdFilter(
                        $productIds
                    );
                    $products->addOptionsToResult();

                    // Set products to items
                    foreach ($collection as $item) {
                        $productId = $item->getProductId();
                        $product = $products->getItemById($productId);
                        if ($product) {
                            $item->setProduct($product);
                        } else {
                            $collection->removeItemByKey($item->getId());
                        }
                    }
                }
            }
            $this->setData('items_collection', $productIds ? $collection : parent::getItemsCollection());
        }
        return $this->getData('items_collection');
    }

    /**
     * Return grid URL for sorting and filtering
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('checkout/*/viewOrdered', ['_current' => true]);
    }
}
