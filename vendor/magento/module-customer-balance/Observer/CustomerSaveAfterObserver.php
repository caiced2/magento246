<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Observer;

use Magento\Framework\Event\ObserverInterface;

class CustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * Customer balance data
     *
     * @var \Magento\CustomerBalance\Helper\Data
     */
    protected $_customerBalanceData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerBalance\Model\BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * Constructor
     *
     * @param \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CustomerBalance\Helper\Data $customerBalanceData
     */
    public function __construct(
        \Magento\CustomerBalance\Model\BalanceFactory $balanceFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CustomerBalance\Helper\Data $customerBalanceData
    ) {
        $this->_balanceFactory = $balanceFactory;
        $this->_storeManager = $storeManager;
        $this->_customerBalanceData = $customerBalanceData;
    }

    /**
     * Customer balance update after save
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->_customerBalanceData->isEnabled()) {
            return;
        }
        /* @var $request \Magento\Framework\App\RequestInterface */
        $request = $observer->getRequest();
        $data = $request->getPost('customerbalance');
        /* @var $customer \Magento\Customer\Api\Data\CustomerInterface */
        $customer = $observer->getCustomer();
        if ($data && !empty($data['amount_delta'])) {
            $balance = $this->_balanceFactory->create()->setCustomer($customer)
                ->setWebsiteId(isset($data['website_id']) ? $data['website_id'] : $customer->getWebsiteId())
                ->setAmountDelta($data['amount_delta'])->setComment($data['comment']);
            if (isset($data['notify_by_email']) && !empty($data['notify_by_email'])) {
                if (isset($data['store_id'])) {
                    $balance->setNotifyByEmail(true, $data['store_id']);
                } elseif ($this->_storeManager->isSingleStoreMode()) {
                    $stores = $this->_storeManager->getStores();
                    $singleStore = array_shift($stores);
                    $balance->setNotifyByEmail(true, $singleStore->getId());
                }
            }
            $balance->save();
        }
    }
}
