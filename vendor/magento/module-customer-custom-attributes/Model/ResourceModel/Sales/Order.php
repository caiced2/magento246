<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\ResourceModel\Sales;

/**
 * Customer Order resource
 */
class Order extends AbstractSales
{
    /**
     * Main entity resource model
     *
     * @var \Magento\Sales\Model\ResourceModel\Order
     */
    protected $_parentResourceModel;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order $parentResourceModel
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Sales\Model\ResourceModel\Order $parentResourceModel,
        $connectionName = null
    ) {
        $this->_parentResourceModel = $parentResourceModel;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_customercustomattributes_sales_flat_order', 'entity_id');
    }

    /**
     * Return resource model of the main entity
     *
     * @return \Magento\Sales\Model\ResourceModel\Order
     */
    protected function _getParentResourceModel()
    {
        return $this->_parentResourceModel;
    }
}
