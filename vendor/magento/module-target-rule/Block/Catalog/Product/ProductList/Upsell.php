<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * TargetRule Catalog Product List Upsell Block
 *
 */
namespace Magento\TargetRule\Block\Catalog\Product\ProductList;

/**
 * @api
 * @since 100.0.2
 */
class Upsell extends \Magento\TargetRule\Block\Catalog\Product\ProductList\AbstractProductList
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\TargetRule\Model\ResourceModel\Index $index
     * @param \Magento\TargetRule\Helper\Data $targetRuleData
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\Product\Visibility $visibility
     * @param \Magento\TargetRule\Model\IndexFactory $indexFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\TargetRule\Model\ResourceModel\Index $index,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $visibility,
        \Magento\TargetRule\Model\IndexFactory $indexFactory,
        \Magento\Checkout\Model\Cart $cart,
        array $data = []
    ) {
        $this->_cart = $cart;
        parent::__construct(
            $context,
            $index,
            $targetRuleData,
            $productCollectionFactory,
            $visibility,
            $indexFactory,
            $data
        );
    }

    /**
     * Retrieve Catalog Product List Type identifier
     *
     * @return int
     */
    public function getProductListType()
    {
        return \Magento\TargetRule\Model\Rule::UP_SELLS;
    }

    /**
     * Retrieve related product collection assigned to product
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getLinkCollection()
    {
        if ($this->_linkCollection === null) {
            parent::getLinkCollection();
            /**
             * Updating collection with desired items
             */
            $this->_eventManager->dispatch(
                'catalog_product_upsell',
                [
                    'product' => $this->getProduct(),
                    'collection' => $this->_linkCollection,
                    'limit' => $this->getPositionLimit()
                ]
            );
        }

        return $this->_linkCollection;
    }

    /**
     * Get ids of all related products
     *
     * @return array
     */
    public function getAllIds()
    {
        if ($this->_allProductIds === null) {
            if (!$this->isShuffled()) {
                return parent::getAllIds();
            }

            $ids = parent::getAllIds();
            $ids = new \Magento\Framework\DataObject(['items' => array_flip($ids)]);
            /**
             * Updating collection with desired items
             */
            $this->_eventManager->dispatch(
                'catalog_product_upsell',
                ['product' => $this->getProduct(), 'collection' => $ids, 'limit' => null]
            );

            $this->_allProductIds = array_keys($ids->getItems());
            shuffle($this->_allProductIds);
        }

        return $this->_allProductIds;
    }

    /**
     * Get all items
     *
     * @return array
     */
    public function getAllItems()
    {
        $collection = parent::getAllItems();
        $collectionMock = new \Magento\Framework\DataObject(['items' => $collection]);
        $this->_eventManager->dispatch(
            'catalog_product_upsell',
            [
                'product'       => $this->getProduct(),
                'collection'    => $collectionMock,
                'limit'         => null
            ]
        );
        return $collectionMock->getItems();
    }

    /**
     * Retrieve array of exclude product ids
     * Rewrite for exclude shopping cart products
     *
     * @return array
     */
    public function getExcludeProductIds()
    {
        if ($this->_excludeProductIds === null) {
            $cartProductIds = $this->_cart->getProductIds();
            $this->_excludeProductIds = array_merge($cartProductIds, [$this->getProduct()->getEntityId()]);
        }
        return $this->_excludeProductIds;
    }
}
