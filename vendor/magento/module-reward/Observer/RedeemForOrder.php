<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class RedeemForOrder implements ObserverInterface
{
    /**
     * Reward place order restriction interface
     *
     * @var \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
     */
    protected $_restriction;

    /**
     * Reward model factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_modelFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward balance validator
     *
     * @var \Magento\Reward\Model\Reward\Balance\Validator
     */
    protected $_validator;

    /**
     * Reward helper.
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $rewardHelper;

    /**
     * @param \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $modelFactory
     * @param \Magento\Reward\Model\Reward\Balance\Validator $validator
     */
    public function __construct(
        \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $modelFactory,
        \Magento\Reward\Model\Reward\Balance\Validator $validator
    ) {
        $this->_restriction = $restriction;
        $this->_storeManager = $storeManager;
        $this->_modelFactory = $modelFactory;
        $this->_validator = $validator;
    }

    /**
     * Reduce reward points if points was used during checkout
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (false == $this->_restriction->isAllowed()) {
            return;
        }

        $event = $observer->getEvent();
        /* @var $order \Magento\Sales\Model\Order */
        $order = $event->getOrder();
        /** @var $quote \Magento\Quote\Model\Quote $quote */
        $quote = $event->getQuote();
        /** @var  \Magento\Quote\Model\Quote\Address $address */
        $address = $event->getAddress();
        if ($quote->getBaseRewardCurrencyAmount() > 0) {
            $this->_validator->validate($order);
            $rewardData = $quote->getIsMultiShipping() ? $address : $quote;

            /** @var $rewardModel \Magento\Reward\Model\Reward */
            $rewardModel = $this->_modelFactory->create();
            $rewardModel->setCustomerId($order->getCustomerId());
            $rewardModel->setWebsiteId($this->_storeManager->getStore($order->getStoreId())->getWebsiteId());

            $rewardModel->setPointsDelta(-$rewardData->getRewardPointsBalance());
            $rewardModel->setAction(\Magento\Reward\Model\Reward::REWARD_ACTION_ORDER);
            $rewardModel->setActionEntity($order);
            $rewardModel->updateRewardPoints();
            $order->setRewardPointsBalance(round($rewardData->getRewardPointsBalance()));
            $order->setRewardCurrencyAmount($rewardData->getRewardCurrencyAmount());
            $order->setBaseRewardCurrencyAmount($rewardData->getBaseRewardCurrencyAmount());
        }
    }
}
