<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance\Balance;

/**
 * @api
 * @since 100.0.2
 */
class Grid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        array $data = []
    ) {
        $this->_balanceFactory = $balanceFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_balanceFactory->create()->getCollection()->addFieldToFilter(
            'customer_id',
            $this->getRequest()->getParam('id')
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
}
