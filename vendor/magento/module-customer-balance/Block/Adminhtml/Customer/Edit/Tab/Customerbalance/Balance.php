<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Block\Adminhtml\Customer\Edit\Tab\Customerbalance;

use Magento\Customer\Controller\RegistryConstants;

/**
 * @api
 * @since 100.0.2
 */
class Balance extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get delete orphan balances button
     *
     * @return string
     */
    public function getDeleteOrphanBalancesButton()
    {
        $customerId = $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $balance = $this->_balanceFactory->create();
        if ($balance->getOrphanBalancesCount($customerId) > 0) {
            return $this->getLayout()->createBlock(
                \Magento\Backend\Block\Widget\Button::class
            )->setData(
                [
                    'label' => __('Delete Orphan Balances'),
                    'onclick' => 'setLocation(\'' . $this->getDeleteOrphanBalancesUrl() . '\')',
                    'class' => 'scalable delete',
                ]
            )->toHtml();
        }
        return '';
    }

    /**
     * Get delete orphan balances url
     *
     * @return string
     */
    public function getDeleteOrphanBalancesUrl()
    {
        return $this->getUrl(
            'adminhtml/customerbalance/deleteOrphanBalances',
            ['_current' => true, 'tab' => 'customer_info_tabs_customerbalance']
        );
    }
}
