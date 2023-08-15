<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Model;

/**
 * Customer balance model
 *
 * @method int getCustomerId()
 * @method \Magento\CustomerBalance\Model\Balance setCustomerId(int $value)
 * @method \Magento\CustomerBalance\Model\Balance setWebsiteId(int $value)
 * @method \Magento\CustomerBalance\Model\Balance setAmount(float $value)
 * @method string getBaseCurrencyCode()
 * @method \Magento\CustomerBalance\Model\Balance setBaseCurrencyCode(string $value)
 * @method \Magento\CustomerBalance\Model\Balance setAmountDelta(float $value)
 * @method \Magento\CustomerBalance\Model\Balance setComment(string $value)
 * @method \Magento\CustomerBalance\Model\Balance setCustomer(\Magento\Customer\Model\Customer $customer)
 */
class Balance extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customer;

    /**
     * @var string
     */
    protected $_eventPrefix = 'customer_balance';

    /**
     * @var string
     */
    protected $_eventObject = 'balance';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\CustomerBalance\Model\Balance\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\CustomerBalance\Model\Balance\HistoryFactory $historyFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\CustomerBalance\Model\Balance\HistoryFactory $historyFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_historyFactory = $historyFactory;
        $this->_storeManager = $storeManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\CustomerBalance\Model\ResourceModel\Balance::class);
    }

    /**
     * Get balance amount
     *
     * @return float
     */
    public function getAmount()
    {
        return (double)$this->getData('amount');
    }

    /**
     * Load balance by customer
     *
     * Website id should either be set or not admin
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByCustomer()
    {
        $this->_ensureCustomer();
        $this->getResource()->loadByCustomerAndWebsiteIds($this, $this->getCustomerId(), $this->getWebsiteId());
        return $this;
    }

    /**
     * Get website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        if ($this->hasWebsiteId()) {
            return $this->_getData('website_id');
        }
        return $this->_storeManager->getStore()->getWebsiteId();
    }

    /**
     * Specify whether email notification should be sent
     *
     * @param bool $shouldNotify
     * @param int|null $storeId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setNotifyByEmail($shouldNotify, $storeId = null)
    {
        $this->setData('notify_by_email', $shouldNotify);
        if ($shouldNotify) {
            if (null === $storeId) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please also set the Store ID.'));
            }
            $this->setStoreId($storeId);
        }
        return $this;
    }

    /**
     * Validate before saving
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave()
    {
        $this->_ensureCustomer();

        if (0 == $this->getWebsiteId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please set a website ID.'));
        }

        // check history action
        if (!$this->getId()) {
            $this->loadByCustomer();
            if (!$this->getId()) {
                $this->setHistoryAction(\Magento\CustomerBalance\Model\Balance\History::ACTION_CREATED);
            }
        }
        if (!$this->hasHistoryAction()) {
            $this->setHistoryAction(\Magento\CustomerBalance\Model\Balance\History::ACTION_UPDATED);
        }

        // check balance delta and email notification settings
        $delta = $this->_prepareAmountDelta();
        if (0 == $delta) {
            $this->setNotifyByEmail(false);
        }
        if ($this->getNotifyByEmail() && !$this->hasStoreId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please enter a store ID to send email notifications.')
            );
        }

        return parent::beforeSave();
    }

    /**
     * Update history after saving
     *
     * @return $this
     */
    public function afterSave()
    {
        parent::afterSave();

        // save history action
        if (abs($this->getAmountDelta())) {
            $this->_historyFactory->create()->setBalanceModel($this)->save();
        }

        return $this;
    }

    /**
     * Make sure proper customer information is set. Load customer if required
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _ensureCustomer()
    {
        if ($this->getCustomer() && $this->getCustomer()->getId()) {
            $this->setCustomerId($this->getCustomer()->getId());
        }
        if (!$this->getCustomerId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please specify a customer ID.'));
        }
        if (!$this->getCustomer()) {
            $this->setCustomer($this->_customerFactory->create()->load($this->getCustomerId()));
        }
        if (!$this->getCustomer()->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please specify a valid customer.')
            );
        }
    }

    /**
     * Validate & adjust amount change
     *
     * @return float
     */
    protected function _prepareAmountDelta()
    {
        $result = 0;
        if ($this->hasAmountDelta()) {
            $result = (double)$this->getAmountDelta();
            if ($this->getId()) {
                if ($result < 0 && $this->getAmount() + $result < 0) {
                    $result = -1 * $this->getAmount();
                }
            } elseif ($result <= 0) {
                $result = 0;
            }
        }
        $this->setAmountDelta($result);
        if (!$this->getId()) {
            $this->setAmount($result);
        } else {
            $this->setAmount($this->getAmount() + $result);
        }
        return $result;
    }

    /**
     * Check whether balance completely covers specified quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param bool $isEstimation
     * @return bool
     */
    public function isFullAmountCovered(\Magento\Quote\Model\Quote $quote, $isEstimation = false)
    {
        if (!$isEstimation && !$quote->getUseCustomerBalance()) {
            return false;
        }
        return $this->getAmount() >=
            (double)$quote->getBaseGrandTotal() + (double)$quote->getBaseCustomerBalAmountUsed();
    }

    /**
     * Update customers balance currency code per website id
     *
     * @param int $websiteId
     * @param string $currencyCode
     * @return $this
     */
    public function setCustomersBalanceCurrencyTo($websiteId, $currencyCode)
    {
        $this->getResource()->setCustomersBalanceCurrencyTo($websiteId, $currencyCode);
        return $this;
    }

    /**
     * Delete customer orphan balances
     *
     * @param int $customerId
     * @return $this
     */
    public function deleteBalancesByCustomerId($customerId)
    {
        $this->getResource()->deleteBalancesByCustomerId($customerId);
        return $this;
    }

    /**
     * Get customer orphan balances count
     *
     * @param int $customerId
     * @return $this
     */
    public function getOrphanBalancesCount($customerId)
    {
        return $this->getResource()->getOrphanBalancesCount($customerId);
    }

    /**
     * Public version of afterLoad
     *
     * @return $this
     */
    public function afterLoad()
    {
        return $this->_afterLoad();
    }
}
