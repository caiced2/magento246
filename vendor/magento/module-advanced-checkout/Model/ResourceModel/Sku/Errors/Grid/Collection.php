<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Model\ResourceModel\Sku\Errors\Grid;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;

/**
 * SKU failed grid collection
 */
class Collection extends \Magento\Framework\Data\Collection
{
    /**
     * @var \Magento\AdvancedCheckout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_productModel;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Magento\AdvancedCheckout\Model\Cart $cart
     * @param \Magento\Catalog\Model\Product $productModel
     * @param PriceCurrencyInterface $priceCurrency
     * @param StockRegistryInterface $stockRegistry
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Magento\AdvancedCheckout\Model\Cart $cart,
        \Magento\Catalog\Model\Product $productModel,
        PriceCurrencyInterface $priceCurrency,
        StockRegistryInterface $stockRegistry
    ) {
        $this->_cart = $cart;
        $this->_productModel = $productModel;
        $this->priceCurrency = $priceCurrency;
        $this->stockRegistry = $stockRegistry;
        parent::__construct($entityFactory);
    }

    /**
     * @param bool $printQuery
     * @param bool $logQuery
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->isLoaded()) {
            $parentBlock = $this->_cart;
            foreach ($parentBlock->getFailedItems() as $affectedItem) {
                // Escape user-submitted input
                if (isset($affectedItem['item']['qty'])) {
                    $affectedItem['item']['qty'] = empty($affectedItem['item']['qty'])
                        ? ''
                        : (float) $affectedItem['item']['qty'];
                }
                $item = new \Magento\Framework\DataObject();
                $item->setCode($affectedItem['code']);
                if (isset($affectedItem['error'])) {
                    $item->setError($affectedItem['error']);
                }
                $item->addData($affectedItem['item']);
                $item->setId($item->getSku());
                $product = clone $this->_productModel;
                if (isset($affectedItem['item']['id'])) {
                    $productId = $affectedItem['item']['id'];
                    $item->setProductId($productId);
                    $product->load($productId);
                    $stockStatus = $this->stockRegistry->getStockStatus($productId, $this->getWebsiteId());
                    if ($stockStatus !== null) {
                        $product->setIsSalable($stockStatus->getStockStatus());
                    }
                    $item->setPrice($this->priceCurrency->format($product->getPrice()));
                }
                $item->setProduct($product);
                $this->addItem($item);
            }
            $this->_setIsLoaded(true);
        }
        return $this;
    }

    /**
     * Get current website ID
     *
     * @codeCoverageIgnore
     * @return int|null|string
     */
    public function getWebsiteId()
    {
        return $this->_cart->getStore()->getWebsiteId();
    }
}
