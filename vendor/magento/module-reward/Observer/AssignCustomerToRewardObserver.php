<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Assign customer created after guest order to reward.
 */
class AssignCustomerToRewardObserver implements ObserverInterface
{
    /**
     * Reward place order restriction interface
     *
     * @var \Magento\Reward\Observer\PlaceOrder\RestrictionInterface
     */
    private $restriction;

    /**
     * Reward model factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    private $modelFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Reward helper.
     *
     * @var \Magento\Reward\Helper\Data
     */
    private $rewardHelper;

    /**
     * @var RewardPointCounter
     */
    private $rewardPointCounter;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param PlaceOrder\RestrictionInterface $restriction
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $modelFactory
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param RewardPointCounter $rewardPointCounter
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        \Magento\Reward\Observer\PlaceOrder\RestrictionInterface $restriction,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $modelFactory,
        \Magento\Reward\Helper\Data $rewardHelper,
        RewardPointCounter $rewardPointCounter,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->restriction = $restriction;
        $this->storeManager = $storeManager;
        $this->modelFactory = $modelFactory;
        $this->rewardHelper = $rewardHelper;
        $this->rewardPointCounter = $rewardPointCounter;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Increase reward points balance for sales rules applied to order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->restriction->isAllowed() === false) {
            return;
        }

        /** @var array $delegateData */
        $delegateData = $observer->getEvent()->getData('delegate_data');
        if (array_key_exists('__sales_assign_order_id', $delegateData)) {
            $orderId = $delegateData['__sales_assign_order_id'];
            $order = $this->orderRepository->get($orderId);

            if (!$order->getAppliedRuleIds()) {
                return;
            }

            $appliedRuleIds = array_unique(explode(',', $order->getAppliedRuleIds()));
            $pointsDelta = $this->rewardPointCounter->getPointsForRules($appliedRuleIds);

            if ($pointsDelta && !$order->getCustomerIsGuest() && $order->getCustomerId()) {
                $reward = $this->modelFactory->create();
                $reward->setCustomerId(
                    $order->getCustomerId()
                )->setWebsiteId(
                    $this->storeManager->getStore($order->getStoreId())->getWebsiteId()
                )->setPointsDelta(
                    $pointsDelta
                )->setAction(
                    \Magento\Reward\Model\Reward::REWARD_ACTION_SALESRULE
                )->setActionEntity(
                    $order
                )->updateRewardPoints();

                $order->addStatusHistoryComment(
                    __(
                        'Customer earned promotion extra %1.',
                        $this->rewardHelper->formatReward($pointsDelta)
                    )
                );
            }
        }
    }
}
