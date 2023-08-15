<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Quote\Model\Quote;
use Magento\Reward\Helper\Data as RewardHelper;
use Magento\Reward\Model\Reward;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var RewardHelper
     */
    protected $rewardHelper;

    /**
     * @var Reward
     */
    protected $reward;

    /**
     * @var RewardFactory
     */
    protected $rewardFactory;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param CustomerSession $customerSession
     * @param StoreManagerInterface $storeManager
     * @param CheckoutSession $checkoutSession
     * @param RewardFactory $rewardFactory
     * @param RewardHelper $rewardHelper
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        CheckoutSession $checkoutSession,
        RewardFactory $rewardFactory,
        RewardHelper $rewardHelper,
        UrlInterface $urlBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->rewardFactory = $rewardFactory;
        $this->rewardHelper = $rewardHelper;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [
            'authentication' => [
                'reward' => [
                    'isAvailable' => $this->isAuthTooltipAvailable(),
                    'tooltipLearnMoreUrl' => $this->getRewardTooltipLearnMoreUrl(),
                    'tooltipMessage' => $this->getAuthRewardTooltip(),
                ],
            ],
            'payment' => [
                'reward' => [
                    'isAvailable' => $this->isAvailable(),
                    'amountSubstracted' => (bool)$this->getQuote()->getUseRewardPoints() ? true : false,
                    'usedAmount' => (float)$this->getQuote()->getBaseRewardCurrencyAmount(),
                    'balance' => (float)$this->getRewardModel()->getCurrencyAmount(),
                    'label' => $this->getRewardLabel()
                ],
            ],
            'review' => [
                'reward' => [
                    'removeUrl' => $this->getRewardRemoveUrl(),
                ],
            ],
        ];
        return $config;
    }

    /**
     * Check if reward points is available
     *
     * @return bool
     */
    protected function isAvailable()
    {
        if (!$this->rewardHelper->getHasRates()
            || !$this->rewardHelper->isEnabledOnFront()) {
            return false;
        }

        $minPointsToUse = $this->rewardHelper->getGeneralConfig(
            'min_points_balance',
            (int)$this->storeManager->getWebsite()->getId()
        );

        return (float)$this->getRewardModel()->getCurrencyAmount() > 0
            && $this->getRewardModel()->getPointsBalance() >= $minPointsToUse;
    }

    /**
     * Get reward point instance
     *
     * @return Reward
     */
    protected function getRewardModel()
    {
        if (!$this->reward) {
            $this->reward = $this->rewardFactory->create()
                ->setCustomerId($this->customerSession->getCustomerId())
                ->setWebsiteId($this->storeManager->getStore()->getWebsiteId())
                ->loadByCustomer();
        }
        return $this->reward;
    }

    /**
     * Retrieve Quote object
     *
     * @return Quote
     */
    protected function getQuote()
    {
        if (!$this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    /**
     * Retrieve reward label
     *
     * @return \Magento\Framework\Phrase
     */
    protected function getRewardLabel()
    {
        $format = '%s';
        $points = sprintf($format, $this->getRewardModel()->getPointsBalance());
        if (null !== $this->getRewardModel()->getCurrencyAmount() && $this->rewardHelper->getHasRates()) {
            $amount = sprintf(
                $format,
                $this->rewardHelper->formatAmount($this->getRewardModel()->getCurrencyAmount(), true, null)
            );
            return __('%1 store reward points available (%2)', $points, $amount);
        }
        return __('%1 store reward points available', $points);
    }

    /**
     * Retrieve reward remove URL
     *
     * @return string
     */
    protected function getRewardRemoveUrl()
    {
        return $this->urlBuilder->getUrl('magento_reward/cart/remove');
    }

    /**
     * Retrieve reward tooltip 'Learn More' link URL
     *
     * @return string
     */
    protected function getRewardTooltipLearnMoreUrl()
    {
        return $this->rewardHelper->getLandingPageUrl();
    }

    /**
     * Retrieve reward tooltip for authentication
     *
     * @return string
     */
    protected function getAuthRewardTooltip()
    {
        /** @var \Magento\Reward\Model\Action\OrderExtra $action */
        $action = $this->getRewardModel()
            ->getActionInstance(\Magento\Reward\Model\Action\OrderExtra::class, true);
        $action->setQuote($this->getQuote());

        $rewardMessage = $this->rewardHelper->formatReward(
            $this->getRewardModel()->estimateRewardPoints($action),
            $this->getRewardModel()->estimateRewardAmount($action)
        );

        return __('Sign in now and earn %1 for this order.', $rewardMessage);
    }

    /**
     * Check if reward tooltip for authentication is available
     *
     * @return bool
     */
    protected function isAuthTooltipAvailable()
    {
        if (!$this->rewardHelper->getHasRates()
            || !$this->rewardHelper->isEnabledOnFront()
            || !$this->rewardHelper->isOrderAllowed()) {
            return false;
        }
        return true;
    }
}
