<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion;

/**
 * Accordion grid for Recently compared products
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Rcompared extends AbstractAccordion
{
    /**
     * Javascript list type name for this grid
     *
     * @var string
     */
    protected $_listType = 'rcompared';

    /**
     * @var \Magento\Sales\Helper\Admin
     */
    protected $_adminhtmlSales;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory
     */
    protected $_compareListFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productListFactory;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Event
     */
    protected $_reportsEventResource;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Reports\Model\ResourceModel\Event $reportsEventResource
     * @param \Magento\Sales\Helper\Admin $adminhtmlSales
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $compareListFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
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
        \Magento\Reports\Model\ResourceModel\Event $reportsEventResource,
        \Magento\Sales\Helper\Admin $adminhtmlSales,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productListFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory $compareListFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        array $data = []
    ) {
        $this->_catalogConfig = $catalogConfig;
        $this->_reportsEventResource = $reportsEventResource;
        $this->_adminhtmlSales = $adminhtmlSales;
        $this->productListFactory = $productListFactory;
        $this->_compareListFactory = $compareListFactory;
        $this->stockRegistry = $stockRegistry;
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
        $this->setId('source_rcompared');
        if ($this->_getStore()) {
            $this->setHeaderText(__('Recently Compared Products (%1)', $this->getItemsCount()));
        }
    }

    /**
     * Return items collection
     *
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getItemsCollection()
    {
        if (!$this->hasData('items_collection')) {
            $skipProducts = [];
            $collection = $this->_compareListFactory->create();
            $collection->useProductItem(true)
                ->setStoreId($this->_getStore()->getId())
                ->addStoreFilter($this->_getStore()->getId())
                ->setCustomerId($this->_getCustomer()->getId());

            foreach ($collection as $item) {
                $skipProducts[] = $item->getProductId();
            }

            // prepare products collection and apply visitors log to it
            $attributes = $this->_catalogConfig->getProductAttributes();
            if (!in_array('status', $attributes)) {
                // Status attribute is required even if it is not used in product listings
                $attributes[] = 'status';
            }
            $productCollection = $this->productListFactory->create()
                ->setStoreId($this->_getStore()->getId())
                ->addStoreFilter($this->_getStore()->getId())
                ->addAttributeToSelect($attributes);

            $this->_reportsEventResource->applyLogToCollection(
                $productCollection,
                \Magento\Reports\Model\Event::EVENT_PRODUCT_COMPARE,
                $this->_getCustomer()->getId(),
                0,
                $skipProducts
            );
            $productCollection = $this->_adminhtmlSales->applySalableProductTypesFilter($productCollection);
            // Remove disabled and out of stock products from the grid
            foreach ($productCollection as $product) {
                $stockItem = $this->stockRegistry->getStockItem($product->getId(), $this->_getStore()->getWebsiteId());
                if (!$stockItem->getIsInStock() || !$product->isInStock()) {
                    $productCollection->removeItemByKey($product->getId());
                }
            }
            $productCollection->addOptionsToResult();
            $this->setData('items_collection', $productCollection);
        }
        return $this->_getData('items_collection');
    }

    /**
     * Retrieve Grid URL
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('checkout/*/viewRecentlyCompared', ['_current' => true]);
    }
}
