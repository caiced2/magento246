<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Model\ResourceModel\Giftcard;

/**
 * Gift card amount resource model
 */
class Amount extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $connectionName = null
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * Define main table and primary key
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftcard_amount', 'value_id');
    }

    /**
     * Load product data by product and attribute_id
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return array
     */
    public function loadProductData($product, $attribute)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['website_id', 'value']
        )->where(
            $product->getResource()->getLinkField() . '=:product_id'
        )->where(
            'attribute_id=:attribute_id'
        );
        $bind = ['product_id' => $product->getId(), 'attribute_id' => $attribute->getId()];
        if ($attribute->isScopeGlobal()) {
            $select->where('website_id=0');
        } else {
            if ($storeId = $product->getStoreId()) {
                $select->where('website_id IN (0, :website_id)');
                $bind['website_id'] = $this->_storeManager->getStore($storeId)->getWebsiteId();
            }
        }
        return $connection->fetchAll($select, $bind);
    }

    /**
     * Delete product data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return $this
     */
    public function deleteProductData($product, $attribute)
    {
        $condition = [];

        if (!$attribute->isScopeGlobal()) {
            if ($storeId = $product->getStoreId()) {
                $condition['website_id IN (?)'] = [0, $this->_storeManager->getStore($storeId)->getWebsiteId()];
            }
        }

        $condition['entity_id=?'] = $product->getId();
        $condition['attribute_id=?'] = $attribute->getId();

        $this->getConnection()->delete($this->getMainTable(), $condition);
        return $this;
    }

    /**
     * Insert product data
     *
     * @param array $data
     * @return int
     */
    public function insert($data)
    {
        return $this->getConnection()->insert($this->getMainTable(), $data);
    }
}
