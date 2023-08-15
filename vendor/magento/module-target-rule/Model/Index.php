<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Model;

/**
 * @api
 * @since 100.0.2
 */
class Index extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Target rule data
     *
     * @var \Magento\TargetRule\Helper\Data
     */
    protected $_targetRuleData = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_ruleCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\TargetRule\Helper\Data $targetRuleData
     * @param \Magento\TargetRule\Model\ResourceModel\Index $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory $ruleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $session,
        \Magento\TargetRule\Helper\Data $targetRuleData,
        \Magento\TargetRule\Model\ResourceModel\Index $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_ruleCollectionFactory = $ruleFactory;
        $this->_storeManager = $storeManager;
        $this->_session = $session;
        $this->_targetRuleData = $targetRuleData;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\TargetRule\Model\ResourceModel\Index::class);
    }

    /**
     * Set Catalog Product List identifier
     *
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        return $this->setData('type', $type);
    }

    /**
     * Retrieve Catalog Product List identifier
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return int
     */
    public function getType()
    {
        $type = $this->getData('type');
        if ($type === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The Catalog Product List Type needs to be defined. Verify the type and try again.')
            );
        }
        return $type;
    }

    /**
     * Set store scope
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData('store_id', $storeId);
    }

    /**
     * Retrieve store identifier scope
     *
     * @return int
     */
    public function getStoreId()
    {
        $storeId = $this->getData('store_id');
        if ($storeId === null) {
            $storeId = $this->_storeManager->getStore()->getId();
        }
        return $storeId;
    }

    /**
     * Set customer group identifier
     *
     * @param int $customerGroupId
     * @return $this
     */
    public function setCustomerGroupId($customerGroupId)
    {
        return $this->setData('customer_group_id', $customerGroupId);
    }

    /**
     * Retrieve customer group identifier
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        $customerGroupId = $this->getData('customer_group_id');
        if ($customerGroupId === null) {
            $customerGroupId = $this->_session->getCustomerGroupId();
        }
        return $customerGroupId;
    }

    /**
     * Set result limit
     *
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        return $this->setData('limit', $limit);
    }

    /**
     * Retrieve result limit
     *
     * @return int
     */
    public function getLimit()
    {
        $limit = $this->getData('limit');
        if ($limit === null) {
            $limit = $this->_targetRuleData->getMaximumNumberOfProduct($this->getType());
        }
        return $limit;
    }

    /**
     * Set Product data object
     *
     * @param \Magento\Framework\DataObject $product
     * @return $this
     */
    public function setProduct(\Magento\Framework\DataObject $product)
    {
        return $this->setData('product', $product);
    }

    /**
     * Retrieve Product data object
     *
     * @return \Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProduct()
    {
        $product = $this->getData('product');
        if (!$product instanceof \Magento\Framework\DataObject) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please define a product data object.'));
        }
        return $product;
    }

    /**
     * Set product ids list be excluded
     *
     * @param int|array $productIds
     * @return $this
     */
    public function setExcludeProductIds($productIds)
    {
        if (!is_array($productIds)) {
            $productIds = [$productIds];
        }
        return $this->setData('exclude_product_ids', $productIds);
    }

    /**
     * Retrieve Product Ids which must be excluded
     *
     * @return array
     */
    public function getExcludeProductIds()
    {
        $productIds = $this->getData('exclude_product_ids');
        if (!is_array($productIds)) {
            $productIds = [];
        }
        return $productIds;
    }

    /**
     * Retrieve related product Ids
     *
     * @return array
     */
    public function getProductIds()
    {
        return $this->_getResource()->getProductIds($this);
    }

    /**
     * Retrieve Rule collection by type and product
     *
     * @return \Magento\TargetRule\Model\ResourceModel\Rule\Collection
     */
    public function getRuleCollection()
    {
        /* @var $collection \Magento\TargetRule\Model\ResourceModel\Rule\Collection */
        $collection = $this->_ruleCollectionFactory->create();
        $collection->addApplyToFilter(
            $this->getType()
        )->addProductFilter(
            $this->getProduct()->getId()
        )->addIsActiveFilter()->setPriorityOrder()->setFlag(
            'do_not_run_after_load',
            true
        );

        return $collection;
    }

    /**
     * Retrieve SELECT instance for conditions
     *
     * @return \Magento\Framework\DB\Select
     */
    public function select()
    {
        return $this->_getResource()->select();
    }
}
