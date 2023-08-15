<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Item;

/**
 * Gift registry item option model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Option extends \Magento\Framework\Model\AbstractModel implements
    \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface
{
    /**
     * Related gift registry item
     *
     * @var \Magento\GiftRegistry\Model\Item
     */
    protected $_item;

    /**
     * Product related to option
     *
     * @var \Magento\Catalog\Model\Product $product
     */
    protected $_product;

    /**
     * Internal constructor
     * Initializes resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftRegistry\Model\ResourceModel\Item\Option::class);
    }

    /**
     * Checks if item option model has data changes
     *
     * @return boolean
     */
    protected function _hasModelChanged()
    {
        if (!$this->hasDataChanges()) {
            return false;
        }

        return $this->_getResource()->hasDataChanged($this);
    }

    /**
     * Set related gift registry item
     *
     * @param   \Magento\GiftRegistry\Model\Item $item
     * @return $this
     */
    public function setItem($item)
    {
        $this->setItemId($item->getId());
        $this->_item = $item;
        return $this;
    }

    /**
     * Retrieve related gift registry item
     *
     * @return \Magento\GiftRegistry\Model\Item
     */
    public function getItem()
    {
        return $this->_item;
    }

    /**
     * Set product related to option
     *
     * @param   \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function setProduct($product)
    {
        if (!empty($product) && $product->getId() !== null) {
            $this->setProductId($product->getId());
            $this->_product = $product;
        }
        return $this;
    }

    /**
     * Retrieve product related to option
     *
     * @return \Magento\Catalog\Model\Product
     * @codeCoverageIgnore
     */
    public function getProduct()
    {
        return $this->_product;
    }

    /**
     * Get option value
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getValue()
    {
        return $this->_getData('value');
    }

    /**
     * Initialize item identifier before data save
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->getItem()) {
            $this->setItemId($this->getItem()->getId());
        }
        return parent::beforeSave();
    }

    /**
     * Clone option object
     *
     * @return $this
     */
    public function __clone()
    {
        $this->setId(null);
        $this->_item = null;
        return $this;
    }
}
