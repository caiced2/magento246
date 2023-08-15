<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote;

/**
 * Customer Quote Address resource model
 */
class Address extends \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Address\AbstractAddress
{
    /**
     * Main entity resource model
     *
     * @var \Magento\Quote\Model\ResourceModel\Quote\Address
     */
    protected $_parentResourceModel;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address $parentResourceModel
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Quote\Model\ResourceModel\Quote\Address $parentResourceModel,
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
        $this->_init('magento_customercustomattributes_sales_flat_quote_address', 'entity_id');
    }

    /**
     * Return resource model of the main entity
     *
     * @return \Magento\Quote\Model\ResourceModel\Quote\Address
     */
    protected function _getParentResourceModel()
    {
        return $this->_parentResourceModel;
    }
}
