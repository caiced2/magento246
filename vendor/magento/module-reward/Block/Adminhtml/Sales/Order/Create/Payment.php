<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward Points Payment block in admin order creating process
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Block\Adminhtml\Sales\Order\Create;

/**
 * @api
 * @since 100.0.2
 */
class Payment extends \Magento\Backend\Block\Template
{
    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * @var \Magento\Sales\Model\AdminOrder\Create
     */
    protected $_orderCreate;

    /**
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Sales\Model\AdminOrder\Create $orderCreate
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Sales\Model\AdminOrder\Create $orderCreate,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        array $data = []
    ) {
        $this->_rewardData = $rewardData;
        $this->_orderCreate = $orderCreate;
        $this->_rewardFactory = $rewardFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        parent::__construct($context, $data);
    }

    /**
     * Getter
     *
     * @return \Magento\Quote\Model\Quote
     * @codeCoverageIgnore
     */
    public function getQuote()
    {
        return $this->_orderCreate->getQuote();
    }

    /**
     * Check whether can use customer reward points
     *
     * @return bool
     */
    public function canUseRewardPoints()
    {
        $websiteId = $this->_storeManager->getStore($this->getQuote()->getStoreId())->getWebsiteId();
        $minPointsBalance = (int)$this->_scopeConfig->getValue(
            \Magento\Reward\Model\Reward::XML_PATH_MIN_POINTS_BALANCE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getQuote()->getStoreId()
        );

        return $this->getReward()->getPointsBalance() >= $minPointsBalance
        && $this->_rewardData->isEnabledOnFront(
            $websiteId
        )
        && $this->_authorization->isAllowed(
            \Magento\Reward\Helper\Data::XML_PATH_PERMISSION_AFFECT
        )
        && (double)$this->getCurrencyAmount()
        && $this->getQuote()->getBaseGrandTotal() + $this->getQuote()->getBaseRewardCurrencyAmount() > 0;
    }

    /**
     * Getter.
     * Retrieve reward points model
     *
     * @return \Magento\Reward\Model\Reward
     */
    public function getReward()
    {
        if (!$this->_getData('reward')) {
            $customer = $this->getQuote()->getCustomer();
            /* @var $reward \Magento\Reward\Model\Reward */
            $reward = $this->_rewardFactory->create()->setCustomer($customer);
            $reward->setStore($this->getQuote()->getStore());
            $reward->loadByCustomer();
            $this->setData('reward', $reward);
        }
        return $this->_getData('reward');
    }

    /**
     * Prepare some template data
     *
     * @return string
     * @codeCoverageIgnore
     */
    protected function _toHtml()
    {
        $points = $this->getReward()->getPointsBalance();
        $amount = $this->getReward()->getCurrencyAmount();
        $rewardFormatted = $this->_rewardData->formatReward($points, $amount, $this->getQuote()->getStore()->getId());
        $this->setPointsBalance(
            $points
        )->setCurrencyAmount(
            $amount
        )->setUseLabel(
            __('Use your reward points; %1 are available.', $rewardFormatted)
        );
        return parent::_toHtml();
    }

    /**
     * Check if reward points applied in quote
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function useRewardPoints()
    {
        return (bool)$this->_orderCreate->getQuote()->getUseRewardPoints();
    }
}
