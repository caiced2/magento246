<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Advertising Tooltip block to show different messages for gaining reward points
 */
namespace Magento\Reward\Block;

/**
 * @api
 * @since 100.0.2
 */
class Tooltip extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardHelper;

    /**
     * Reward instance
     *
     * @var \Magento\Reward\Model\Reward
     */
    protected $_rewardInstance;

    /**
     * Reward action instance
     *
     * @var \Magento\Reward\Model\Action\AbstractAction
     */
    protected $_actionInstance;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Reward\Model\Reward $rewardInstance
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Reward\Helper\Data $rewardHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Reward\Model\Reward $rewardInstance,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_customerSession = $customerSession;
        $this->_rewardHelper = $rewardHelper;
        $this->_rewardInstance = $rewardInstance;
        $this->_isScopePrivate = true;
    }

    /**
     * @return $this|\Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $action = $this->getRewardType();
        if ($action) {
            if (!$this->_rewardHelper->isEnabledOnFront()) {
                return $this;
            }
            $this->_rewardInstance->setWebsiteId(
                $this->_storeManager->getStore()->getWebsiteId()
            )->setCustomer(
                $this->_customerSession->getCustomer()
            )->setWebsiteId(
                $this->_storeManager->getStore()->getWebsiteId()
            )->loadByCustomer();
            $this->_actionInstance = $this->_rewardInstance->getActionInstance($action, true);
        }
        return $this;
    }

    /**
     * Getter for amount customer may be rewarded for current action
     * Can format as currency
     *
     * @param float $amount
     * @param bool $asCurrency
     * @return string|null
     */
    public function getRewardAmount($amount = null, $asCurrency = false)
    {
        $amount = null === $amount ? $this->_getData('reward_amount') : $amount;
        return $this->_rewardHelper->formatAmount($amount, $asCurrency);
    }

    /**
     * @param string $format
     * @param null|string $anchorText
     * @return string
     */
    public function renderLearnMoreLink($format = '<a href="%1$s">%2$s</a>', $anchorText = null)
    {
        $anchorText = null === $anchorText ? __('Learn more') : $anchorText;
        return sprintf($format, $this->getLandingPageUrl(), $anchorText);
    }

    /**
     * Set various template variables
     *
     * @return void
     */
    protected function _prepareTemplateData()
    {
        if ($this->_actionInstance) {
            $this->addData(
                [
                    'reward_points' => $this->_rewardInstance->estimateRewardPoints($this->_actionInstance),
                    'landing_page_url' => $this->_rewardHelper->getLandingPageUrl(),
                ]
            );

            if ($this->_rewardInstance->getId()) {
                // estimate qty limitations (actually can be used without customer reward record)
                $qtyLimit = $this->_actionInstance->estimateRewardsQtyLimit();
                if (null !== $qtyLimit) {
                    $this->setData('qty_limit', $qtyLimit);
                }

                if ($this->hasGuestNote()) {
                    $this->unsGuestNote();
                }

                $this->addData(
                    [
                        'points_balance' => $this->_rewardInstance->getPointsBalance(),
                        'currency_balance' => $this->_rewardInstance->getCurrencyAmount(),
                    ]
                );
                // estimate monetary reward
                $amount = $this->_rewardInstance->estimateRewardAmount($this->_actionInstance);
                if (null !== $amount) {
                    $this->setData('reward_amount', $amount);
                }
            } else {
                if ($this->hasIsGuestNote() && !$this->hasGuestNote()) {
                    $this->setGuestNote(
                        __('This applies only to registered users and may vary when a user is logged in.')
                    );
                }
            }
        }
    }

    /**
     * Check whether everything is set for output
     *
     * @return string
     */
    protected function _toHtml()
    {
        $this->_prepareTemplateData();
        if (!$this->_actionInstance || !$this->getRewardPoints() || $this->hasQtyLimit() && !$this->getQtyLimit()) {
            return '';
        }
        return parent::_toHtml();
    }
}
