<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Reward Helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Reward\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * XML configuration paths - section general
     *
     * @var string
     */
    const XML_PATH_SECTION_GENERAL = 'magento_reward/general/';

    /**
     * XML configuration paths - section points
     *
     * @var string
     */
    const XML_PATH_SECTION_POINTS = 'magento_reward/points/';

    /**
     * XML configuration paths - section notifications
     *
     * @var string
     */
    const XML_PATH_SECTION_NOTIFICATIONS = 'magento_reward/notification/';

    /**
     * XML configuration paths - path enabled
     *
     * @var string
     */
    const XML_PATH_ENABLED = 'magento_reward/general/is_enabled';

    /**
     * XML configuration paths - landing page
     *
     * @var string
     */
    const XML_PATH_LANDING_PAGE = 'magento_reward/general/landing_page';

    /**
     * XML configuration paths - auto refund
     *
     * @var string
     */
    const XML_PATH_AUTO_REFUND = 'magento_reward/general/refund_automatically';

    /**
     * XML configuration paths - permission balance
     *
     * @var string
     */
    const XML_PATH_PERMISSION_BALANCE = 'Magento_Reward::reward_balance';

    /**
     * XML configuration paths - permission affect
     *
     * @var string
     */
    const XML_PATH_PERMISSION_AFFECT = 'Magento_Reward::reward_spend';

    /**
     * @var array
     */
    protected $_expiryConfig;

    /**
     * @var bool $_hasRates
     */
    protected $_hasRates = true;

    /**
     * @var null
     */
    protected $_ratesArray = null;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     */
    protected $_localeCurrency;

    /**
     * @var \Magento\Reward\Model\ResourceModel\Reward\Rate\CollectionFactory
     */
    protected $_ratesFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Reward\Model\ResourceModel\Reward\Rate\CollectionFactory $ratesFactory
     * @param PriceCurrencyInterface $priceCurrency
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Reward\Model\ResourceModel\Reward\Rate\CollectionFactory $ratesFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;
        $this->_localeCurrency = $localeCurrency;
        $this->_ratesFactory = $ratesFactory;
        parent::__construct($context);
    }

    /**
     * Setter for hasRates flag
     *
     * @param bool $flag
     * @return $this
     * @codeCoverageIgnore
     */
    public function setHasRates($flag)
    {
        $this->_hasRates = $flag;
        return $this;
    }

    /**
     * Getter for hasRates flag
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getHasRates()
    {
        return $this->_hasRates;
    }

    /**
     * Check whether reward module is enabled in system config
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check whether reward module is enabled in system config on front per website
     *
     * @param int $websiteId
     * @return bool
     */
    public function isEnabledOnFront($websiteId = null)
    {
        if ($websiteId === null) {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        }
        return $this->isEnabled() && $this->getGeneralConfig('is_enabled_on_front', (int)$websiteId);
    }

    /**
     * Check whether reward points can be gained for spending money
     *
     * @param int $websiteId
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function isOrderAllowed($websiteId = null)
    {
        if ($websiteId === null) {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        }
        return $allowed = (bool)(int)$this->getPointsConfig('order', $websiteId);
    }

    /**
     * Retrieve value of given field and website from config
     *
     * @param string $section
     * @param string $field
     * @param int $websiteId
     * @return string
     * @codeCoverageIgnore
     */
    public function getConfigValue($section, $field, $websiteId = null)
    {
        $code = $this->_storeManager->getWebsite($websiteId)->getCode();
        return (string)$this->scopeConfig->getValue($section . $field, 'website', $code);
    }

    /**
     * Retrieve config value from General section
     *
     * @param string $field
     * @param int $websiteId
     * @return string
     * @codeCoverageIgnore
     */
    public function getGeneralConfig($field, $websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SECTION_GENERAL, $field, $websiteId);
    }

    /**
     * Retrieve config value from Points section
     *
     * @param string $field
     * @param int $websiteId
     * @return string
     * @codeCoverageIgnore
     */
    public function getPointsConfig($field, $websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SECTION_POINTS, $field, $websiteId);
    }

    /**
     * Retrieve config value from Notification section
     *
     * @param string $field
     * @param int $websiteId
     * @return string
     * @codeCoverageIgnore
     */
    public function getNotificationConfig($field, $websiteId = null)
    {
        return $this->getConfigValue(self::XML_PATH_SECTION_NOTIFICATIONS, $field, $websiteId);
    }

    /**
     * Return acc array of websites expiration points config
     *
     * @return array
     */
    public function getExpiryConfig()
    {
        if ($this->_expiryConfig === null) {
            $result = [];
            foreach ($this->_storeManager->getWebsites() as $website) {
                $websiteId = $website->getId();
                $result[$websiteId] = new \Magento\Framework\DataObject(
                    [
                        'expiration_days' => $this->getGeneralConfig('expiration_days', $websiteId),
                        'expiry_calculation' => $this->getGeneralConfig('expiry_calculation', $websiteId),
                        'expiry_day_before' => $this->getNotificationConfig('expiry_day_before', $websiteId),
                    ]
                );
            }
            $this->_expiryConfig = $result;
        }

        return $this->_expiryConfig;
    }

    /**
     * Format (add + or - sign) before given points count
     *
     * @param int $points
     * @return string
     */
    public function formatPointsDelta($points)
    {
        $formatedPoints = $points;
        if ($points > 0) {
            $formatedPoints = '+' . $points;
        } elseif ($points < 0) {
            $formatedPoints = '-' . -1 * $points;
        }
        return $formatedPoints;
    }

    /**
     * Getter for "Learn More" landing page URL
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLandingPageUrl()
    {
        $pageIdentifier = $this->scopeConfig->getValue(
            self::XML_PATH_LANDING_PAGE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $this->_urlBuilder->getUrl('', ['_direct' => $pageIdentifier]);
    }

    /**
     * Render a reward message as X points Y money
     *
     * @param int $points
     * @param float|null $amount
     * @param int|null $storeId
     * @param string $pointsFormat
     * @param string $amountFormat
     * @return \Magento\Framework\Phrase
     */
    public function formatReward($points, $amount = null, $storeId = null, $pointsFormat = '%s', $amountFormat = '%s')
    {
        $points = sprintf($pointsFormat, $points);
        if (null !== $amount && $this->getHasRates()) {
            $amount = sprintf($amountFormat, $this->formatAmount($amount, true, $storeId));
            return __('%1 Reward points (%2)', $points, $amount);
        }
        return __('%1 Reward points', $points);
    }

    /**
     * Format an amount as currency or rounded value
     *
     * @param float|string|null $amount
     * @param bool $asCurrency
     * @param int|null $storeId
     * @return string|null
     */
    public function formatAmount($amount, $asCurrency = true, $storeId = null)
    {
        if (null === $amount) {
            return null;
        }
        return $asCurrency
            ? $this->priceCurrency->convertAndFormat(
                $amount,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $this->_storeManager->getStore($storeId)
            )
            : sprintf('%.2F', $amount);
    }

    /**
     * Format points to currency rate
     *
     * @param int $points
     * @param float $amount
     * @param string $currencyCode
     * @return string
     * @codeCoverageIgnore
     */
    public function formatRateToCurrency($points, $amount, $currencyCode = null)
    {
        return $this->_formatRate('%1$s points = %2$s', $points, $amount, $currencyCode);
    }

    /**
     * Format currency to points rate
     *
     * @param int $points
     * @param float $amount
     * @param string $currencyCode
     * @return string
     * @codeCoverageIgnore
     */
    public function formatRateToPoints($points, $amount, $currencyCode = null)
    {
        return $this->_formatRate('%2$s = %1$s points', $points, $amount, $currencyCode);
    }

    /**
     * Format rate according to format
     *
     * @param string $format
     * @param int $points
     * @param float $amount
     * @param string $currencyCode
     * @return string
     */
    protected function _formatRate($format, $points, $amount, $currencyCode)
    {
        $points = (int)$points;
        if (!$currencyCode) {
            $amountFormatted = sprintf('%.2F', $amount);
        } else {
            $amountFormatted = $this->_localeCurrency->getCurrency($currencyCode)->toCurrency((double)$amount);
        }
        return sprintf($format, $points, $amountFormatted);
    }

    /**
     * Loading history collection data
     * and Setting up rate to currency array
     *
     * @return array
     */
    protected function _loadRatesArray()
    {
        $ratesArray = [];
        $collection = $this->_ratesFactory->create()->addFieldToFilter(
            'direction',
            \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY
        );
        foreach ($collection as $rate) {
            $ratesArray[$rate->getCustomerGroupId()][$rate->getWebsiteId()] = $rate;
        }
        return $ratesArray;
    }

    /**
     * Fetch rate for given website_id and group_id from index_array
     * @param int $points
     * @param int $websiteId
     * @param int $customerGroupId
     * @return string|null
     */
    public function getRateFromRatesArray($points, $websiteId, $customerGroupId)
    {
        if (!$this->_ratesArray) {
            $this->_ratesArray = $this->_loadRatesArray();
        }
        $rate = null;
        if (isset($this->_ratesArray[$customerGroupId])) {
            if (isset($this->_ratesArray[$customerGroupId][$websiteId])) {
                $rate = $this->_ratesArray[$customerGroupId][$websiteId];
            } elseif (isset($this->_ratesArray[$customerGroupId][0])) {
                $rate = $this->_ratesArray[$customerGroupId][0];
            }
        } elseif (isset($this->_ratesArray[0])) {
            if (isset($this->_ratesArray[0][$websiteId])) {
                $rate = $this->_ratesArray[0][$websiteId];
            } elseif (isset($this->_ratesArray[0][0])) {
                $rate = $this->_ratesArray[0][0];
            }
        }
        if ($rate !== null) {
            return $rate->calculateToCurrency($points);
        }
        return null;
    }

    /**
     * Check if automatically refund is enabled
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isAutoRefundEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_AUTO_REFUND,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
