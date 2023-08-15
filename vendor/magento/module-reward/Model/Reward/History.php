<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Reward;

/**
 * Reward history model
 *
 * @method int getRewardId()
 * @method \Magento\Reward\Model\Reward\History setRewardId(int $value)
 * @method int getWebsiteId()
 * @method \Magento\Reward\Model\Reward\History setWebsiteId(int $value)
 * @method int getStoreId()
 * @method \Magento\Reward\Model\Reward\History setStoreId(int $value)
 * @method int getAction()
 * @method \Magento\Reward\Model\Reward\History setAction(int $value)
 * @method int getEntity()
 * @method \Magento\Reward\Model\Reward\History setEntity(int $value)
 * @method int getPointsBalance()
 * @method \Magento\Reward\Model\Reward\History setPointsBalance(int $value)
 * @method int getPointsDelta()
 * @method \Magento\Reward\Model\Reward\History setPointsDelta(int $value)
 * @method int getPointsUsed()
 * @method \Magento\Reward\Model\Reward\History setPointsUsed(int $value)
 * @method int getPointsVoided()
 * @method \Magento\Reward\Model\Reward\History setPointsVoided(int $value)
 * @method float getCurrencyAmount()
 * @method \Magento\Reward\Model\Reward\History setCurrencyAmount(float $value)
 * @method float getCurrencyDelta()
 * @method \Magento\Reward\Model\Reward\History setCurrencyDelta(float $value)
 * @method string getBaseCurrencyCode()
 * @method \Magento\Reward\Model\Reward\History setBaseCurrencyCode(string $value)
 * @method \Magento\Reward\Model\Reward\History setAdditionalData(string $value)
 * @method string getComment()
 * @method \Magento\Reward\Model\Reward\History setComment(string $value)
 * @method string getCreatedAt()
 * @method \Magento\Reward\Model\Reward\History setCreatedAt(string $value)
 * @method string getExpiredAtStatic()
 * @method \Magento\Reward\Model\Reward\History setExpiredAtStatic(string $value)
 * @method string getExpiredAtDynamic()
 * @method \Magento\Reward\Model\Reward\History setExpiredAtDynamic(string $value)
 * @method int getIsExpired()
 * @method \Magento\Reward\Model\Reward\History setIsExpired(int $value)
 * @method int getIsDuplicateOf()
 * @method \Magento\Reward\Model\Reward\History setIsDuplicateOf(int $value)
 * @method int getNotificationSent()
 * @method \Magento\Reward\Model\Reward\History setNotificationSent(int $value)
 * @api
 * @since 100.0.2
 */
class History extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * Core model store manager interface
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Reward model
     *
     * @var \Magento\Reward\Model\Reward
     */
    protected $_reward;

    /**
     * Date time formatter
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * Reward rate model
     *
     * @var Rate
     */
    protected $rewardRate;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Reward\Model\ResourceModel\Reward\History $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Reward\Model\Reward $reward
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Reward\Model\Reward\Rate $rewardRate
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Reward\Model\ResourceModel\Reward\History $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Reward\Model\Reward $reward,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Reward\Model\Reward\Rate $rewardRate,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_reward = $reward;
        $this->dateTime = $dateTime;
        $this->rewardRate = $rewardRate;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Reward\Model\ResourceModel\Reward\History::class);
    }

    /**
     * Processing object before save data.
     * Prepare history data
     *
     * @return $this
     */
    public function beforeSave()
    {
        if ($this->getWebsiteId()) {
            $this->setBaseCurrencyCode($this->_storeManager->getWebsite($this->getWebsiteId())->getBaseCurrencyCode());
        }
        if ($this->getPointsDelta() < 0) {
            $this->_spendAvailablePoints($this->getPointsDelta());
        }

        $now = time();
        $this->addData(
            [
                'created_at' => $this->dateTime->formatDate($now),
                'expired_at_static' => null,
                'expired_at_dynamic' => null,
                'notification_sent' => 0,
            ]
        );

        $lifetime = (int)$this->_rewardData->getGeneralConfig('expiration_days', $this->getWebsiteId());
        if ($lifetime > 0) {
            $expires = $now + $lifetime * 86400;
            $expires = $this->dateTime->formatDate($expires);
            $this->addData(['expired_at_static' => $expires, 'expired_at_dynamic' => $expires]);
        }

        return parent::beforeSave();
    }

    /**
     * Setter
     *
     * @param \Magento\Reward\Model\Reward $reward
     * @return $this
     * @codeCoverageIgnore
     */
    public function setReward($reward)
    {
        $this->_reward = $reward;
        return $this;
    }

    /**
     * Getter
     *
     * @return \Magento\Reward\Model\Reward
     * @codeCoverageIgnore
     */
    public function getReward()
    {
        return $this->_reward;
    }

    /**
     * Create history data from reward object
     *
     * @return $this
     */
    public function prepareFromReward()
    {
        $store = $this->getReward()->getStore();
        if ($store === null) {
            $store = $this->_storeManager->getStore();
        }
        $this->setRewardId(
            $this->getReward()->getId()
        )->setWebsiteId(
            $this->getReward()->getWebsiteId()
        )->setStoreId(
            $store->getId()
        )->setPointsBalance(
            $this->getReward()->getPointsBalance()
        )->setPointsDelta(
            $this->getReward()->getPointsDelta()
        )->setCurrencyAmount(
            $this->getReward()->getCurrencyAmount()
        )->setCurrencyDelta(
            $this->getReward()->getCurrencyDelta()
        )->setAction(
            $this->getReward()->getAction()
        )->setComment(
            $this->getReward()->getComment()
        );

        $this->addAdditionalData(
            [
                'rate' => [
                    'points' => $this->getReward()->getRate()->getPoints(),
                    'currency_amount' => $this->getReward()->getRate()->getCurrencyAmount(),
                    'direction' => $this->getReward()->getRate()->getDirection(),
                    'currency_code' => $this->_storeManager->getWebsite(
                        $this->getReward()->getWebsiteId()
                    )->getBaseCurrencyCode(),
                ],
            ]
        );

        if ($this->getReward()->getIsCappedReward()) {
            $this->addAdditionalData(
                ['is_capped_reward' => true, 'cropped_points' => $this->getReward()->getCroppedPoints()]
            );
        }
        return $this;
    }

    /**
     * Retrieve additional data of an arbitrary structure
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public function getAdditionalData()
    {
        $result = $this->hasData('additional_data') ? $this->_getData('additional_data') : [];
        if (!is_array($result)) {
            throw new \UnexpectedValueException('Additional data for a reward point history has to be an array.');
        }
        return $result;
    }

    /**
     * Retrieve value of additional data's field
     *
     * @param string $key
     * @return mixed|null
     */
    public function getAdditionalDataByKey($key)
    {
        $data = $this->getAdditionalData();
        if (isset($data[$key])) {
            return $data[$key];
        }
        return null;
    }

    /**
     * Add field values to the additional data, overriding values of existing fields
     *
     * @param array $data
     * @return $this
     */
    public function addAdditionalData(array $data)
    {
        $this->setData('additional_data', array_merge($this->getAdditionalData(), $data));
        return $this;
    }

    /**
     * Retrieve translated and prepared message
     *
     * @return string
     */
    public function getMessage()
    {
        if (!$this->hasData('message')) {
            $action = $this->_reward->getActionInstance($this->getAction());
            $message = '';
            if ($action !== null) {
                $message = $action->getHistoryMessage($this->getAdditionalData());
            }
            $this->setData('message', $message);
        }
        return $this->_getData('message');
    }

    /**
     * Rate text getter
     *
     * @return string|null
     */
    public function getRateText()
    {
        $rate = $this->getAdditionalDataByKey('rate');
        if (isset($rate['points']) && isset($rate['currency_amount']) && isset($rate['direction'])) {
            return $this->rewardRate->getRateText(
                (int)$rate['direction'],
                (int)$rate['points'],
                (double)$rate['currency_amount'],
                $this->getBaseCurrencyCode()
            );
        }
    }

    /**
     * Check if history update with given action, customer and entity exist
     *
     * @param int $customerId
     * @param int $action
     * @param int $websiteId
     * @param mixed $entity
     * @return bool
     */
    public function isExistHistoryUpdate($customerId, $action, $websiteId, $entity)
    {
        $result = $this->_getResource()->isExistHistoryUpdate($customerId, $action, $websiteId, $entity);
        return $result;
    }

    /**
     * Return total quantity rewards for specified action and customer
     *
     * @param int $action
     * @param int $customerId
     * @param int $websiteId
     * @return int
     */
    public function getTotalQtyRewards($action, $customerId, $websiteId)
    {
        return $this->_getResource()->getTotalQtyRewards($action, $customerId, $websiteId);
    }

    /**
     * Getter for date when the record is supposed to expire
     *
     * @return string|null
     */
    public function getExpiresAt()
    {
        if ($this->getPointsDelta() <= 0) {
            return null;
        }
        return $this->_rewardData->getGeneralConfig(
            'expiry_calculation'
        ) == 'static' ? $this->getExpiredAtStatic() : $this->getExpiredAtDynamic();
    }

    /**
     * Spend unused points for required amount
     *
     * @param int $required Points total that required
     * @return $this
     */
    protected function _spendAvailablePoints($required)
    {
        $this->getResource()->useAvailablePoints($this, $required);
        return $this;
    }
}
