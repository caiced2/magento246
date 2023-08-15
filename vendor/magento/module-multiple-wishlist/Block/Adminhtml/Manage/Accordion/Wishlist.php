<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Block\Adminhtml\Manage\Accordion;

/**
 * Accordion grid for products in wishlist
 *
 * @api
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Wishlist extends \Magento\AdvancedCheckout\Block\Adminhtml\Manage\Accordion\Wishlist
{
    /**
     * Item collection factory
     *
     * @var \Magento\MultipleWishlist\Model\ResourceModel\Item\Collection
     */
    protected $_itemCollectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\MultipleWishlist\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\MultipleWishlist\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory,
        array $data = []
    ) {
        $this->_itemCollectionFactory = $itemCollectionFactory;
        parent::__construct(
            $context,
            $backendHelper,
            $collectionFactory,
            $coreRegistry,
            $itemFactory,
            $stockRegistry,
            $data
        );
    }

    /**
     * Return items collection
     *
     * @return \Magento\Wishlist\Model\ResourceModel\Item\Collection
     */
    protected function _createItemsCollection()
    {
        return $this->_itemCollectionFactory->create();
    }

    /**
     * Prepare Grid columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'wishlist_name',
            ['header' => __('Wishlist'), 'index' => 'wishlist_name', 'sortable' => false]
        );

        return parent::_prepareColumns();
    }
}
