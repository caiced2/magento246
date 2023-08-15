<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Model\Reward\Refund;

use Magento\Framework\App\ObjectManager;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

class SalesRuleRefund
{
    /**
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $rewardFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Reward\Helper\Data
     */
    protected $rewardHelper;

    /**
     * @var RewardPointCounter
     */
    private $rewardPointCounter;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Magento\Reward\Helper\Data $rewardHelper
     * @param RewardPointCounter|null $rewardPointCounter
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Magento\Reward\Helper\Data $rewardHelper,
        RewardPointCounter $rewardPointCounter = null
    ) {
        $this->rewardFactory = $rewardFactory;
        $this->storeManager = $storeManager;
        $this->rewardHelper = $rewardHelper;
        $this->rewardPointCounter = $rewardPointCounter ?: ObjectManager::getInstance()->get(RewardPointCounter::class);
    }

    /**
     * Refund reward points earned by salesRule
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return void
     */
    public function refund(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        /* @var $order \Magento\Sales\Model\Order */
        $order = $creditmemo->getOrder();

        if ($creditmemo->getAutomaticallyCreated()) {
            $rewardPointsBalance = is_numeric($creditmemo->getRewardPointsBalance()) ?
                round($creditmemo->getRewardPointsBalance()) : null;
            $creditmemo->setRewardPointsBalanceRefund($rewardPointsBalance);
        }

        $totalItemsToRefund = $this->getTotalItemsToRefund($creditmemo, $order);
        $rewardPointsToVoid = $this->getRewardPointsToVoid($order);
        if ($this->isAllowedRefund($creditmemo)
            && $rewardPointsToVoid > 0
            && $totalItemsToRefund > 0
            && $order->getTotalQtyOrdered() - $totalItemsToRefund == 0
        ) {
            $rewardModel = $this->getRewardModel([
                'website_id' => $this->storeManager->getStore($order->getStoreId())->getWebsiteId(),
                'customer_id' => $order->getCustomerId(),
                'points_delta' => (-$rewardPointsToVoid),
                'action' => \Magento\Reward\Model\Reward::REWARD_ACTION_CREDITMEMO_VOID,
            ]);
            $rewardModel->setActionEntity($order);
            $rewardModel->save();
        }
    }

    /**
     * Return reward points qty to void
     *
     * @param \Magento\Sales\Model\Order $order
     * @return int
     */
    protected function getRewardPointsToVoid(\Magento\Sales\Model\Order $order)
    {
        $rewardModel = $this->getRewardModel([
            'website_id' => $this->storeManager->getStore($order->getStoreId())->getWebsiteId(),
            'customer_id' => $order->getCustomerId(),
        ]);

        $salesRulePoints = 0;
        if ($order->getAppliedRuleIds()) {
            $appliedRuleIds = array_unique(explode(',', $order->getAppliedRuleIds()));
            $salesRulePoints = $this->rewardPointCounter->getPointsForRules($appliedRuleIds);
        }
        $rewardModel->loadByCustomer();
        if ($rewardModel->getPointsBalance() >= $salesRulePoints) {
            return (int)$salesRulePoints;
        }

        return (int)$rewardModel->getPointsBalance();
    }

    /**
     * Return is refund allowed for creditmemo
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @return bool
     */
    protected function isAllowedRefund(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        return $creditmemo->getAutomaticallyCreated() ? $this->rewardHelper->isAutoRefundEnabled() : true;
    }

    /**
     * Return total amount of items to be refunded, which is the sum of all creditmemo items
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo
     * @param \Magento\Sales\Model\Order $order
     * @return int
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getTotalItemsToRefund(
        \Magento\Sales\Model\Order\Creditmemo $creditmemo,
        \Magento\Sales\Model\Order $order
    ) {
        $totalItemsRefund = 0;
        if ($order->getCreditmemosCollection() !== false) {
            foreach ($order->getCreditmemosCollection() as $creditMemo) {
                foreach ($creditMemo->getAllItems() as $item) {
                    $totalItemsRefund += $item->getQty();
                }
            }
        }
        return (int)$totalItemsRefund;
    }

    /**
     * Return reward model
     *
     * @param array $data
     * @return \Magento\Reward\Model\Reward
     */
    protected function getRewardModel($data = [])
    {
        return $this->rewardFactory->create(['data' => $data]);
    }
}
