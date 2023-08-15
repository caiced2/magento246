<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\Reward;

/**
 * Reward rate model
 *
 * @method int getWebsiteId()
 * @method \Magento\Reward\Model\Reward\Rate setWebsiteId(int $value)
 * @method int getCustomerGroupId()
 * @method \Magento\Reward\Model\Reward\Rate setCustomerGroupId(int $value)
 * @method int getDirection()
 * @method \Magento\Reward\Model\Reward\Rate setDirection(int $value)
 * @method \Magento\Reward\Model\Reward\Rate setPoints(int $value)
 * @method \Magento\Reward\Model\Reward\Rate setCurrencyAmount(float $value)
 *
 * @api
 * @since 100.0.2
 */
class Rate extends \Magento\Framework\Model\AbstractModel
{
    const RATE_EXCHANGE_DIRECTION_TO_CURRENCY = 1;

    const RATE_EXCHANGE_DIRECTION_TO_POINTS = 2;

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
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Reward\Model\ResourceModel\Reward\Rate $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Reward\Model\ResourceModel\Reward\Rate $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_localeCurrency = $localeCurrency;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Rate text getter
     *
     * @param int $direction
     * @param int $points
     * @param float $amount
     * @param string $currencyCode
     * @return string|null
     */
    public function getRateText($direction, $points, $amount, $currencyCode = null)
    {
        switch ($direction) {
            case self::RATE_EXCHANGE_DIRECTION_TO_CURRENCY:
                return $this->_rewardData->formatRateToCurrency($points, $amount, $currencyCode);
            case self::RATE_EXCHANGE_DIRECTION_TO_POINTS:
                return $this->_rewardData->formatRateToPoints($points, $amount, $currencyCode);
            default:
                return null;
        }
    }

    /**
     * Internal constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Reward\Model\ResourceModel\Reward\Rate::class);
    }

    /**
     * Processing object before save data.
     * Prepare rate data
     *
     * @return $this
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $this->_prepareRateValues();
        return $this;
    }

    /**
     * Validate rate data
     *
     * @return true
     * @codeCoverageIgnore
     */
    public function validate()
    {
        return true;
    }

    /**
     * Reset rate data
     *
     * @return $this
     */
    public function reset()
    {
        $this->setData([]);
        return $this;
    }

    /**
     * Check if given rate data (website, customer group, direction)
     * is unique to current (already loaded) rate
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param int $direction
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsRateUniqueToCurrent($websiteId, $customerGroupId, $direction)
    {
        $data = $this->_getResource()->getRateData($websiteId, $customerGroupId, $direction);
        if ($data && $data['rate_id'] != $this->getId()) {
            return false;
        }
        return true;
    }

    /**
     * Prepare values in order to defined direction
     *
     * @return $this
     */
    protected function _prepareRateValues()
    {
        if ($this->_getData('direction') == self::RATE_EXCHANGE_DIRECTION_TO_CURRENCY) {
            $this->setData('points', (int)$this->_getData('value'));
            $this->setData('currency_amount', (double)$this->_getData('equal_value'));
        } elseif ($this->_getData('direction') == self::RATE_EXCHANGE_DIRECTION_TO_POINTS) {
            $this->setData('currency_amount', (double)$this->_getData('value'));
            $this->setData('points', (int)$this->_getData('equal_value'));
        }
        return $this;
    }

    /**
     * Fetch rate by customer group and website
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @param int $direction
     * @return $this
     */
    public function fetch($customerGroupId, $websiteId, $direction)
    {
        $this->setData('original_website_id', $websiteId)->setData('original_customer_group_id', $customerGroupId);
        $this->_getResource()->fetch($this, $customerGroupId, $websiteId, $direction);
        return $this;
    }

    /**
     * Calculate currency amount of given points by rate
     *
     * @param int $points
     * @param bool $rounded whether to round points to integer or not
     * @return float
     */
    public function calculateToCurrency($points, $rounded = true)
    {
        $amount = 0;
        if ($this->getPoints()) {
            if ($rounded) {
                $roundedPoints = (int)($points / $this->getPoints());
            } else {
                $roundedPoints = round($points / $this->getPoints(), 2);
            }
            if ($roundedPoints) {
                $amount = $this->getCurrencyAmount() * $roundedPoints;
            }
        }
        return (double)$amount;
    }

    /**
     * Calculate points of given amount by rate
     *
     * @param float $amount
     * @return integer
     */
    public function calculateToPoints($amount)
    {
        $points = 0;
        if ($this->getCurrencyAmount() && $amount >= $this->getCurrencyAmount()) {
            /**
             * Type casting made in such way to avoid wrong automatic type casting and calculation.
             * $amount always int and $this->getCurrencyAmount() is string or float
             */
            $amountValue = (int)((string)$amount / (string)$this->getCurrencyAmount());
            if ($amountValue) {
                $points = $this->getPoints() * $amountValue;
            }
        }
        return $points;
    }

    /**
     * Retrieve option array of rate directions with labels
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getDirectionsOptionArray()
    {
        $optArray = [
            self::RATE_EXCHANGE_DIRECTION_TO_CURRENCY => __('Points to Currency'),
            self::RATE_EXCHANGE_DIRECTION_TO_POINTS => __('Currency to Points'),
        ];
        return $optArray;
    }

    /**
     * Getter for currency part of the rate
     * Formatted value returns string
     *
     * @param bool $formatted
     * @return mixed|string
     */
    public function getCurrencyAmount($formatted = false)
    {
        $amount = $this->_getData('currency_amount');
        if ($formatted) {
            $websiteId = $this->getOriginalWebsiteId();
            if ($websiteId === null) {
                $websiteId = $this->getWebsiteId();
            }
            $currencyCode = $this->_storeManager->getWebsite($websiteId)->getBaseCurrencyCode();
            return $this->_localeCurrency->getCurrency($currencyCode)->toCurrency($amount);
        }
        return $amount;
    }

    /**
     * Getter for points part of the rate
     * Formatted value returns as int
     *
     * @param bool $formatted
     * @return mixed|int
     */
    public function getPoints($formatted = false)
    {
        $pts = $this->_getData('points');
        return $formatted ? (int)$pts : $pts;
    }
}
