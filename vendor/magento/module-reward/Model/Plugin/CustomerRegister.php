<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Plugin;

/**
 * Plugin for saving reward notification attributes on customer register
 */
class CustomerRegister
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

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
     * Customer registry
     *
     * @var \Magento\Customer\Model\CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\RewardFactory $rewardFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\CustomerRegistry $customerRegistry
     */
    public function __construct(
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\RewardFactory $rewardFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_rewardFactory = $rewardFactory;
        $this->_logger = $logger;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Save reward notification attributes and reward after customer account create
     *
     * @param \Magento\Customer\Model\AccountManagement $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterCreateAccountWithPasswordHash(
        \Magento\Customer\Model\AccountManagement $subject,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ) {
        if (!$this->_rewardData->isEnabledOnFront()) {
            return $customer;
        }

        $subscribeByDefault = $this->_rewardData->getNotificationConfig(
            'subscribe_by_default',
            $this->_storeManager->getStore()->getWebsiteId()
        );

        try {
            $customerModel = $this->customerRegistry
                ->retrieveByEmail($customer->getEmail());
            $customerModel->setRewardUpdateNotification($subscribeByDefault);
            $customerModel->setRewardWarningNotification($subscribeByDefault);
            $customerModel->getResource()
                ->saveAttribute($customerModel, 'reward_update_notification');
            $customerModel->getResource()
                ->saveAttribute($customerModel, 'reward_warning_notification');

            $this->_rewardFactory->create()->setCustomer(
                $customer
            )->setActionEntity(
                $customer
            )->setStore(
                $this->_storeManager->getStore()->getId()
            )->setAction(
                \Magento\Reward\Model\Reward::REWARD_ACTION_REGISTER
            )->updateRewardPoints();
        } catch (\Exception $e) {
            //save exception if something went wrong during saving reward
            //and allow to register customer
            $this->_logger->critical($e);
        }

        return $customer;
    }
}
