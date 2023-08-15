<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Cron;

class ScheduledBalanceExpireNotification
{
    /**
     * Reward history factory
     *
     * @var \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory
     */
    protected $_historyItemFactory;

    /**
     * Reward history collection
     *
     * @var \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory
     */
    protected $_historyCollectionFactory;

    /**
     * Reward factory
     *
     * @var \Magento\Reward\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward helper
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $_historyCollectionFactory
     * @param \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory $_historyItemFactory
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Magento\Reward\Model\ResourceModel\Reward\History\CollectionFactory $_historyCollectionFactory,
        \Magento\Reward\Model\ResourceModel\Reward\HistoryFactory $_historyItemFactory
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_rewardFactory = $rewardFactory;
        $this->_historyCollectionFactory = $_historyCollectionFactory;
        $this->_historyItemFactory = $_historyItemFactory;
    }

    /**
     * Send scheduled low balance warning notifications
     *
     * @return $this
     */
    public function execute()
    {
        if (!$this->_rewardData->isEnabled()) {
            return $this;
        }

        foreach ($this->_storeManager->getWebsites() as $website) {
            if (!$this->_rewardData->isEnabledOnFront($website->getId())) {
                continue;
            }
            $inDays = (int)$this->_rewardData->getNotificationConfig('expiry_day_before');
            if (!$inDays) {
                continue;
            }
            $collection = $this->_historyCollectionFactory->create()->setExpiryConfig(
                $this->_rewardData->getExpiryConfig()
            )->loadExpiredSoonPoints(
                $website->getId(),
                true
            )->addNotificationSentFlag(
                false
            )->addCustomerInfo()->setPageSize(
                20
            )->setCurPage(
                1
            )->load();

            foreach ($collection as $item) {
                $this->_rewardFactory->create()->sendBalanceWarningNotification($item, $website->getId());
            }

            // mark records as sent
            $historyIds = $collection->getExpiredSoonIds();
            $this->_historyItemFactory->create()->markAsNotified($historyIds);
        }

        return $this;
    }
}
