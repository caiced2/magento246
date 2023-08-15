<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Sales\Model\Order;

/**
 * Increase reward points balance for sales rules applied to orders.
 */
class EarnForOrder implements ObserverInterface
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
     * Reward helper.
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $rewardHelper;

    /**
     * @var RewardPointCounter
     */
    private $rewardPointCounter;

    /**
     * @var OrderStatusHistoryRepositoryInterface
     */
    private $historyRepository;

    /**
     * @param \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $modelFactory
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param RewardPointCounter $rewardPointCounter
     * @param OrderStatusHistoryRepositoryInterface $historyRepository
     */
    public function __construct(
        \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $modelFactory,
        \Magento\Reward\Helper\Data $rewardHelper,
        RewardPointCounter $rewardPointCounter,
        OrderStatusHistoryRepositoryInterface $historyRepository
    ) {
        $this->_restriction = $restriction;
        $this->_storeManager = $storeManager;
        $this->_modelFactory = $modelFactory;
        $this->rewardHelper = $rewardHelper;
        $this->rewardPointCounter = $rewardPointCounter;
        $this->historyRepository = $historyRepository;
    }

    /**
     * Process order.
     *
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        if ($this->_restriction->isAllowed() === false) {
            return;
        }

        $event = $observer->getEvent();
        $orders = $event->getOrders() ?: [$event->getOrder()];
        /* @var $order Order */
        foreach ($orders as $order) {
            $this->saveOrderHistoryComment($order);
        }
    }

    /**
     * Increase reward points balance for sales rules applied to order.
     *
     * @param Order $order
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function saveOrderHistoryComment(Order $order): void
    {
        $appliedRuleIds = $order->getAppliedRuleIds();
        if ($appliedRuleIds === null) {
            $appliedRuleIds = '';
        }
        $appliedRuleIds = array_unique(explode(',', $appliedRuleIds));
        $pointsDelta = $this->rewardPointCounter->getPointsForRules($appliedRuleIds);

        if ($pointsDelta && !$order->getCustomerIsGuest()) {
            $reward = $this->_modelFactory->create();
            $reward->setCustomerId(
                $order->getCustomerId()
            )->setWebsiteId(
                $this->_storeManager->getStore($order->getStoreId())->getWebsiteId()
            )->setPointsDelta(
                $pointsDelta
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_SALESRULE
            )->setActionEntity(
                $order
            )->updateRewardPoints();

            /** @var OrderStatusHistoryInterface $comment */
            $comment = $order->addCommentToStatusHistory(
                __(
                    'Customer earned promotion extra %1.',
                    $this->rewardHelper->formatReward($pointsDelta)
                )
            );

            $this->historyRepository->save($comment);
        }
    }
}
