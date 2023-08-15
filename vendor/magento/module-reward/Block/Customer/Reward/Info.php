<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer account reward points balance block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Block\Customer\Reward;

/**
 * @api
 * @since 100.0.2
 */
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * Reward pts model instance
     *
     * @var \Magento\Reward\Model\Reward
     */
    protected $_rewardInstance = null;

    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        array $data = []
    ) {
        $this->_rewardData = $rewardData;
        $this->_customerSession = $customerSession;
        $this->_rewardFactory = $rewardFactory;
        parent::__construct($context, $data);
        $this->_isScopePrivate = true;
    }

    /**
     * Render if all there is a customer and a balance
     *
     * @return string
     */
    protected function _toHtml()
    {
        $customer = $this->_customerSession->getCustomer();
        if ($customer && $customer->getId()) {
            $this->_rewardInstance = $this->_rewardFactory->create()->setCustomer(
                $customer
            )->setWebsiteId(
                $this->_storeManager->getWebsite()->getId()
            )->loadByCustomer();
            if ($this->_rewardInstance->getId()) {
                $this->_prepareTemplateData();
                return parent::_toHtml();
            }
        }
        return '';
    }

    /**
     * Set various variables requested by template
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _prepareTemplateData()
    {
        $helper = $this->_rewardData;
        $maxBalance = (int)$helper->getGeneralConfig('max_points_balance');
        $minBalance = (int)$helper->getGeneralConfig('min_points_balance');
        $balance = $this->_rewardInstance->getPointsBalance();
        $this->addData(
            [
                'points_balance' => $balance,
                'currency_balance' => $this->_rewardInstance->getCurrencyAmount(),
                'pts_to_amount_rate_pts' => $this->_rewardInstance->getRateToCurrency()->getPoints(true),
                'pts_to_amount_rate_amount' => $this->_rewardInstance->getRateToCurrency()->getCurrencyAmount(),
                'amount_to_pts_rate_amount' => $this->_rewardInstance->getRateToPoints()->getCurrencyAmount(),
                'amount_to_pts_rate_pts' => $this->_rewardInstance->getRateToPoints()->getPoints(true),
                'max_balance' => $maxBalance,
                'is_max_balance_reached' => $balance >= $maxBalance,
                'min_balance' => $minBalance,
                'is_min_balance_reached' => $balance >= $minBalance,
                'expire_in' => (int)$helper->getGeneralConfig('expiration_days'),
                'is_history_published' => (int)$helper->getGeneralConfig('publish_history'),
            ]
        );
    }
}
