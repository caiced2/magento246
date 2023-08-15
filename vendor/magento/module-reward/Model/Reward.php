<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\Customer;

/**
 * Reward model
 *
 * @method int getCustomerId()
 * @method \Magento\Reward\Model\Reward setCustomerId(int $value)
 * @method \Magento\Reward\Model\Reward setWebsiteId(int $value)
 * @method int getPointsBalance()
 * @method \Magento\Reward\Model\Reward setPointsBalance(int $value)
 * @method \Magento\Reward\Model\Reward setWebsiteCurrencyCode(string $value)
 * @method \Magento\Reward\Model\Reward setPointsDelta(int $value)
 * @method \Magento\Reward\Model\Reward setAction(int $value)
 * @method \Magento\Reward\Model\Reward setComment(string $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Reward extends \Magento\Framework\Model\AbstractModel
{
    const XML_PATH_BALANCE_UPDATE_TEMPLATE = 'magento_reward/notification/balance_update_template';

    const XML_PATH_BALANCE_WARNING_TEMPLATE = 'magento_reward/notification/expiry_warning_template';

    const XML_PATH_EMAIL_IDENTITY = 'magento_reward/notification/email_sender';

    const XML_PATH_MIN_POINTS_BALANCE = 'magento_reward/general/min_points_balance';

    const REWARD_ACTION_ADMIN = 0;

    const REWARD_ACTION_ORDER = 1;

    const REWARD_ACTION_REGISTER = 2;

    const REWARD_ACTION_NEWSLETTER = 3;

    const REWARD_ACTION_INVITATION_CUSTOMER = 4;

    const REWARD_ACTION_INVITATION_ORDER = 5;

    const REWARD_ACTION_REVIEW = 6;

    const REWARD_ACTION_ORDER_EXTRA = 8;

    const REWARD_ACTION_CREDITMEMO = 9;

    const REWARD_ACTION_SALESRULE = 10;

    const REWARD_ACTION_REVERT = 11;

    const REWARD_ACTION_CREDITMEMO_VOID = 12;

    /**
     * Model is loaded by customer
     *
     * @var bool
     */
    protected $_modelLoadedByCustomer = false;

    /**
     * Action model
     *
     * @var array
     */
    protected static $_actionModelClasses = [];

    /**
     * Rates
     *
     * @var array
     */
    protected $_rates = [];

    /**
     * Identifies that reward balance was updated or not
     *
     * @var bool
     */
    protected $_rewardPointsUpdated = false;

    /**
     * Reward data
     *
     * @var \Magento\Reward\Helper\Data
     */
    protected $_rewardData = null;

    /**
     * Reward customer
     *
     * @var \Magento\Reward\Helper\Customer
     */
    protected $_rewardCustomer = null;

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
     * Reward history factory
     *
     * @var \Magento\Reward\Model\Reward\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * Reward rate factory
     *
     * @var \Magento\Reward\Model\Reward\RateFactory
     */
    protected $_rateFactory;

    /**
     * Mail transport builder
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * Reward model
     *
     * @var \Magento\Reward\Model\Reward
     */
    protected $_reward;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var ActionFactory
     */
    private $_actionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Reward\Helper\Customer $rewardCustomer
     * @param \Magento\Reward\Helper\Data $rewardData
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Reward\Model\ActionFactory $actionFactory
     * @param \Magento\Reward\Model\Reward\HistoryFactory $historyFactory
     * @param \Magento\Reward\Model\Reward\RateFactory $rateFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Reward\Helper\Customer $rewardCustomer,
        \Magento\Reward\Helper\Data $rewardData,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Reward\Model\ActionFactory $actionFactory,
        \Magento\Reward\Model\Reward\HistoryFactory $historyFactory,
        \Magento\Reward\Model\Reward\RateFactory $rateFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_rewardCustomer = $rewardCustomer;
        $this->_rewardData = $rewardData;
        $this->_storeManager = $storeManager;
        $this->_localeCurrency = $localeCurrency;
        $this->_actionFactory = $actionFactory;
        $this->_historyFactory = $historyFactory;
        $this->_rateFactory = $rateFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Internal constructor
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magento\Reward\Model\ResourceModel\Reward::class);
        self::$_actionModelClasses = self::$_actionModelClasses + [
                self::REWARD_ACTION_ADMIN => \Magento\Reward\Model\Action\Admin::class,
                self::REWARD_ACTION_ORDER => \Magento\Reward\Model\Action\Order::class,
                self::REWARD_ACTION_REGISTER => \Magento\Reward\Model\Action\Register::class,
                self::REWARD_ACTION_NEWSLETTER => \Magento\Reward\Model\Action\Newsletter::class,
                self::REWARD_ACTION_INVITATION_CUSTOMER => \Magento\Reward\Model\Action\InvitationCustomer::class,
                self::REWARD_ACTION_INVITATION_ORDER => \Magento\Reward\Model\Action\InvitationOrder::class,
                self::REWARD_ACTION_REVIEW => \Magento\Reward\Model\Action\Review::class,
                self::REWARD_ACTION_ORDER_EXTRA => \Magento\Reward\Model\Action\OrderExtra::class,
                self::REWARD_ACTION_CREDITMEMO => \Magento\Reward\Model\Action\Creditmemo::class,
                self::REWARD_ACTION_SALESRULE => \Magento\Reward\Model\Action\Salesrule::class,
                self::REWARD_ACTION_REVERT => \Magento\Reward\Model\Action\OrderRevert::class,
                self::REWARD_ACTION_CREDITMEMO_VOID => \Magento\Reward\Model\Action\Creditmemo\VoidAction::class,
            ];
    }

    /**
     * Set action Id and action model class.
     *
     * Check if given action Id is not integer throw exception
     *
     * @param int $actionId
     * @param string $actionModelClass
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    // phpcs:ignore
    public static function setActionModelClass($actionId, $actionModelClass)
    {
        if (!is_int($actionId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The action ID you enter must be a number.')
            );
        }
        self::$_actionModelClasses[$actionId] = $actionModelClass;
    }

    /**
     * Processing object before save data.
     * Load model by customer and website,
     * prepare points data
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->loadByCustomer()->_preparePointsDelta()->_preparePointsBalance();
        return parent::beforeSave();
    }

    /**
     * Processing object after save data.
     *
     * Save reward history
     *
     * @return $this
     */
    public function afterSave()
    {
        if ((int)$this->getPointsDelta() != 0 || $this->getCappedReward()) {
            $this->_prepareCurrencyAmount();
            $this->getHistory()->prepareFromReward()->save();
            $this->sendBalanceUpdateNotification();
        }
        return parent::afterSave();
    }

    /**
     * Return instance of action wrapper
     *
     * @param string|int $action Action code or a factory name
     * @param bool $isFactoryName
     * @return \Magento\Reward\Model\Action\AbstractAction|null
     */
    public function getActionInstance($action, $isFactoryName = false)
    {
        if ($isFactoryName) {
            $action = array_search($action, self::$_actionModelClasses);
            if (!$action) {
                return null;
            }
        }
        $instance = $this->_registry->registry('_reward_actions' . $action);
        if (!$instance) {
            if (array_key_exists($action, self::$_actionModelClasses)) {
                $instance = $this->_actionFactory->create(self::$_actionModelClasses[$action]);
                // setup invariant properties once
                $instance->setAction($action);
                $instance->setReward($this);
                $this->_registry->register('_reward_actions' . $action, $instance);
            } else {
                return null;
            }
        }
        // keep variable properties up-to-date
        $instance->setHistory($this->getHistory());
        if ($this->getActionEntity()) {
            $instance->setEntity($this->getActionEntity());
        }

        return $instance;
    }

    /**
     * Check if can update reward
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function canUpdateRewardPoints()
    {
        return $this->getActionInstance($this->getAction())->canAddRewardPoints();
    }

    /**
     * Getter
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     * @codeCoverageIgnore
     */
    public function getRewardPointsUpdated()
    {
        return $this->_rewardPointsUpdated;
    }

    /**
     * Save reward points
     *
     * @return $this
     * @throws \Exception
     */
    public function updateRewardPoints()
    {
        $this->_rewardPointsUpdated = false;
        if ($this->canUpdateRewardPoints()) {
            try {
                $this->save();
                $this->_rewardPointsUpdated = true;
            } catch (\Exception $e) {
                $this->_rewardPointsUpdated = false;
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Setter.
     *
     * Set customer id
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->setData('customer_id', $customer->getId());
        $this->setData('customer_group_id', $customer->getGroupId());
        $this->setData('customer', $customer);
        return $this;
    }

    /**
     * Getter
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer()
    {
        if (!$this->_getData('customer') && $this->getCustomerId()) {
            $customer = $this->customerRepository->getById($this->getCustomerId());
            $this->setCustomer($customer);
        }
        return $this->_getData('customer');
    }

    /**
     * Getter
     *
     * @return integer
     */
    public function getCustomerGroupId()
    {
        if (!$this->_getData('customer_group_id') && $this->getCustomer()) {
            $this->setData('customer_group_id', $this->getCustomer()->getGroupId());
        }
        return $this->_getData('customer_group_id');
    }

    /**
     * Getter for website_id
     *
     * If website id not set, get it from assigned store
     *
     * @return int
     */
    public function getWebsiteId()
    {
        if (!$this->_getData('website_id') && ($store = $this->getStore())) {
            $this->setData('website_id', $store->getWebsiteId());
        }
        return $this->_getData('website_id');
    }

    /**
     * Getter for store (for emails etc)
     *
     * Trying get store from customer if its not assigned
     *
     * @return \Magento\Store\Model\Store|null
     */
    public function getStore()
    {
        $store = null;
        if ($this->hasData('store')) {
            $store = $this->getData('store');
        } elseif ($this->hasData('store_id')) {
            $this->setData('store', $this->_getData('store_id'));
            $store = $this->_getData('store_id');
        } elseif ($this->getCustomer() && $this->getCustomer()->getStoreId()) {
            $store = $this->getCustomer()->getStoreId();
            $this->setData('store', $store);
        }
        if ($store !== null) {
            return is_object($store) ? $store : $this->_storeManager->getStore($store);
        }
        return $store;
    }

    /**
     * Getter
     *
     * @return integer
     */
    public function getPointsDelta()
    {
        if ($this->_getData('points_delta') === null) {
            $this->_preparePointsDelta();
        }
        return $this->_getData('points_delta');
    }

    /**
     * Getter.
     *
     * Recalculate currency amount if need.
     *
     * @return float
     */
    public function getCurrencyAmount()
    {
        if ($this->_getData('currency_amount') === null) {
            $this->_prepareCurrencyAmount();
        }
        return $this->_getData('currency_amount');
    }

    /**
     * Getter.
     *
     * Return formated currency amount in currency of website
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getFormatedCurrencyAmount()
    {
        $currencyAmount = $this->_localeCurrency->getCurrency(
            $this->getWebsiteCurrencyCode()
        )->toCurrency(
            $this->getCurrencyAmount()
        );
        return $currencyAmount;
    }

    /**
     * Getter
     *
     * @return string
     */
    public function getWebsiteCurrencyCode()
    {
        if (!$this->_getData('website_currency_code')) {
            $this->setData(
                'website_currency_code',
                $this->_storeManager->getWebsite($this->getWebsiteId())->getBaseCurrencyCode()
            );
        }
        return $this->_getData('website_currency_code');
    }

    /**
     * Getter
     *
     * @return \Magento\Reward\Model\Reward\History
     */
    public function getHistory()
    {
        if (!$this->_getData('history')) {
            $this->setData('history', $this->_historyFactory->create());
            $this->getHistory()->setReward($this);
        }
        return $this->_getData('history');
    }

    /**
     * Initialize and fetch if need rate by given direction
     *
     * @param int $direction
     * @return \Magento\Reward\Model\Reward\Rate
     */
    protected function _getRateByDirection($direction)
    {
        if (!isset($this->_rates[$direction])) {
            $this->_rates[$direction] = $this->_rateFactory->create()->fetch(
                $this->getCustomerGroupId(),
                $this->getWebsiteId(),
                $direction
            );
        }
        return $this->_rates[$direction];
    }

    /**
     * Return rate depend on action
     *
     * @return \Magento\Reward\Model\Reward\Rate
     * @codeCoverageIgnore
     */
    public function getRate()
    {
        return $this->_getRateByDirection($this->getRateDirectionByAction());
    }

    /**
     * Return rate to convert points to currency amount
     *
     * @return \Magento\Reward\Model\Reward\Rate
     * @codeCoverageIgnore
     */
    public function getRateToCurrency()
    {
        return $this->_getRateByDirection(\Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY);
    }

    /**
     * Return rate to convert currency amount to points
     *
     * @return \Magento\Reward\Model\Reward\Rate
     * @codeCoverageIgnore
     */
    public function getRateToPoints()
    {
        return $this->_getRateByDirection(\Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS);
    }

    /**
     * Return rate direction by action
     *
     * @return integer
     */
    public function getRateDirectionByAction()
    {
        switch ($this->getAction()) {
            case self::REWARD_ACTION_ORDER_EXTRA:
                $direction = \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_POINTS;
                break;
            default:
                $direction = \Magento\Reward\Model\Reward\Rate::RATE_EXCHANGE_DIRECTION_TO_CURRENCY;
                break;
        }
        return $direction;
    }

    /**
     * Load by customer and website
     *
     * @return $this
     */
    public function loadByCustomer()
    {
        if (!$this->_modelLoadedByCustomer && $this->getCustomerId() && $this->getWebsiteId()) {
            $this->getResource()->loadByCustomerId($this, $this->getCustomerId(), $this->getWebsiteId());
            $this->_modelLoadedByCustomer = true;
        }
        return $this;
    }

    /**
     * Estimate available points reward for specified action
     *
     * @param \Magento\Reward\Model\Action\AbstractAction $action
     * @return int|null
     */
    public function estimateRewardPoints(\Magento\Reward\Model\Action\AbstractAction $action)
    {
        $websiteId = $this->getWebsiteId();
        $uncappedPts = (int)$action->getPoints($websiteId);
        $max = (int)$this->_rewardData->getGeneralConfig('max_points_balance', $websiteId);
        if ($max > 0) {
            return min(max($max - (int)$this->getPointsBalance(), 0), $uncappedPts);
        }
        return $uncappedPts;
    }

    /**
     * Estimate available monetary reward for specified action
     *
     * May take points value or automatically determine from action
     *
     * @param \Magento\Reward\Model\Action\AbstractAction $action
     * @return float|null
     */
    public function estimateRewardAmount(\Magento\Reward\Model\Action\AbstractAction $action)
    {
        if (!$this->getCustomerId()) {
            return null;
        }
        $this->getWebsiteId();
        $rate = $this->getRateToCurrency();
        if (!$rate->getId()) {
            return null;
        }
        return $rate->calculateToCurrency($this->estimateRewardPoints($action), false);
    }

    /**
     * Prepare points delta, get points delta from config by action
     *
     * @return $this
     */
    protected function _preparePointsDelta()
    {
        $delta = 0;
        $action = $this->getActionInstance($this->getAction());
        if ($action !== null) {
            $delta = $action->getPoints($this->getWebsiteId());
        }
        if ($delta) {
            if ($this->hasPointsDelta()) {
                $delta = $delta + $this->getPointsDelta();
            }
            $this->setPointsDelta((int)$delta);
        }
        return $this;
    }

    /**
     * Prepare points balance
     *
     * @return $this
     */
    protected function _preparePointsBalance()
    {
        $points = 0;
        if ($this->hasPointsDelta()) {
            $points = $this->getPointsDelta();
        }
        $pointsBalance = (int)$this->getPointsBalance() + $points;
        $maxPointsBalance = (int)$this->_rewardData->getGeneralConfig('max_points_balance', $this->getWebsiteId());
        if ($maxPointsBalance != 0 && $pointsBalance > $maxPointsBalance) {
            $pointsBalance = $maxPointsBalance;
            $pointsDelta = $maxPointsBalance - (int)$this->getPointsBalance();
            $croppedPoints = (int)$this->getPointsDelta() - $pointsDelta;
            $this->setPointsDelta($pointsDelta)->setIsCappedReward(true)->setCroppedPoints($croppedPoints);
        }
        $this->setPointsBalance($pointsBalance);
        return $this;
    }

    /**
     * Prepare currency amount and currency delta
     *
     * @return $this
     */
    protected function _prepareCurrencyAmount()
    {
        $amount = 0;
        $amountDelta = 0;
        if ($this->hasPointsDelta()) {
            $amountDelta = $this->_convertPointsToCurrency($this->getPointsDelta());
        }
        $amount = $this->_convertPointsToCurrency($this->getPointsBalance());
        $this->setCurrencyDelta((double)$amountDelta);
        $this->setCurrencyAmount((double)$amount);
        return $this;
    }

    /**
     * Convert points to currency
     *
     * @param int $points
     * @return float
     */
    protected function _convertPointsToCurrency($points)
    {
        return $points && $this->getRateToCurrency() ? (double)$this->getRateToCurrency()->calculateToCurrency(
            $points
        ) : 0;
    }

    /**
     * Check is enough points (currency amount) to cover given amount
     *
     * @param float $amount
     * @return bool
     */
    public function isEnoughPointsToCoverAmount($amount)
    {
        return $this->getId() && $this->getCurrencyAmount() >= $amount;
    }

    /**
     * Return points equivalent of given amount.
     *
     * Converting by 'to currency' rate and points round up
     *
     * @param float $amount
     * @return integer
     */
    public function getPointsEquivalent($amount)
    {
        $points = 0;
        if (!$amount) {
            return $points;
        }

        $ratePointsCount = $this->getRateToCurrency()->getPoints();
        $rateCurrencyAmount = $this->getRateToCurrency()->getCurrencyAmount();
        if ($rateCurrencyAmount > 0) {
            $delta = $amount / $rateCurrencyAmount;
            if ($delta > 0) {
                $points = $ratePointsCount * ceil($delta);
            }
        }

        return $points;
    }

    /**
     * Send Balance Update Notification to customer if notification is enabled
     *
     * @return $this
     */
    public function sendBalanceUpdateNotification()
    {
        $customer = $this->getCustomer();
        // workaround for frontend and backend cases (they use different classes to represent customer)
        $notificationRequired = ($customer instanceof Customer)
            ? $customer->getData('reward_update_notification')
            : $this->getUpdateNotificationAttribute($customer);
        if (!$notificationRequired) {
            return $this;
        }
        $delta = (int)$this->getPointsDelta();
        if ($delta == 0) {
            return $this;
        }
        $history = $this->getHistory();
        $store = $this->_storeManager->getStore($this->getStore());

        $templateIdentifier = $this->_scopeConfig->getValue(
            self::XML_PATH_BALANCE_UPDATE_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $from = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $this->_transportBuilder->setTemplateIdentifier(
            $templateIdentifier
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $store->getId()]
        )->setTemplateVars(
            [
                'store' => $store,
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'unsubscription_url' => $this->_rewardCustomer->getUnsubscribeUrl('update', $store->getId()),
                'points_balance' => $this->getPointsBalance(),
                'reward_amount_was' => $this->_rewardData->formatAmount(
                    $this->getCurrencyAmount() - $history->getCurrencyDelta(),
                    true,
                    $store->getStoreId()
                ),
                'reward_amount_now' => $this->_rewardData->formatAmount(
                    $this->getCurrencyAmount(),
                    true,
                    $store->getStoreId()
                ),
                'reward_pts_was' => $this->getPointsBalance() - $delta,
                'reward_pts_change' => $delta,
                'update_message' => $this->getHistory()->getMessage(),
                'update_comment' => $history->getComment(),
            ]
        )->setFrom(
            $from
        )->addTo(
            $this->getCustomer()->getEmail()
        );
        $transport = $this->_transportBuilder->getTransport();
        try {
            $transport->sendMessage();
            $this->setBalanceUpdateSent(true);
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock
        } catch (\Magento\Framework\Exception\MailException $e) {
        }
        return $this;
    }

    /**
     * Send low Balance Warning Notification to customer if notification is enabled
     *
     * @param object $item
     * @param int $websiteId
     * @return $this
     * @see \Magento\Reward\Model\ResourceModel\Reward\History\Collection::loadExpiredSoonPoints()
     */
    public function sendBalanceWarningNotification($item, $websiteId)
    {
        $store = $this->_storeManager->getStore($item->getStoreId());
        $helper = $this->_rewardData;
        $amount = $helper->getRateFromRatesArray(
            $item->getPointsBalanceTotal(),
            $websiteId,
            $item->getCustomerGroupId()
        );
        $action = $this->getActionInstance($item->getAction());

        $templateIdentifier = $this->_scopeConfig->getValue(
            self::XML_PATH_BALANCE_WARNING_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $from = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_IDENTITY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        $remainingDays = $this->_scopeConfig->getValue(
            'magento_reward/notification/expiry_day_before',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        $this->_transportBuilder->setTemplateIdentifier(
            $templateIdentifier
        )->setTemplateOptions(
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $item->getStoreId()]
        )->setTemplateVars(
            [
                'store' => $store,
                'customer_name' => $item->getCustomerFirstname() . ' ' . $item->getCustomerLastname(),
                'unsubscription_url' => $this->_rewardCustomer->getUnsubscribeUrl('warning'),
                'remaining_days' => $remainingDays,
                'points_balance' => $item->getPointsBalanceTotal(),
                'points_expiring' => $item->getTotalExpired(),
                'reward_amount_now' => $helper->formatAmount($amount, true, $item->getStoreId()),
                'update_message' => $action !== null ? $action->getHistoryMessage($item->getAdditionalData()) : '',
            ]
        )->setFrom(
            $from
        )->addTo(
            $item->getCustomerEmail()
        );
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();

        return $this;
    }

    /**
     * Prepare orphan points
     *
     * Prepare orphan points by given website id and website base currency code
     * after website was deleted
     *
     * @param int $websiteId
     * @param string $baseCurrencyCode
     * @return $this
     */
    public function prepareOrphanPoints($websiteId, $baseCurrencyCode)
    {
        if ($websiteId) {
            $this->_getResource()->prepareOrphanPoints($websiteId, $baseCurrencyCode);
        }
        return $this;
    }

    /**
     * Delete orphan (points of deleted website) points by given customer
     *
     * @param \Magento\Customer\Model\Customer|int|null $customer
     * @return $this
     */
    public function deleteOrphanPointsByCustomer($customer = null)
    {
        if ($customer === null) {
            $customer = $this->getCustomerId() ? $this->getCustomerId() : $this->getCustomer();
        }
        if (is_object($customer) && $customer instanceof \Magento\Customer\Model\Customer) {
            $customer = $customer->getId();
        }
        if ($customer) {
            $this->_getResource()->deleteOrphanPointsByCustomer($customer);
        }
        return $this;
    }

    /**
     * Override setter for setting customer group id  from order
     *
     * @param mixed $entity
     * @return $this
     */
    public function setActionEntity($entity)
    {
        if ($entity->getGroupId()) {
            $this->setCustomerGroupId($entity->getGroupId());
        }
        return parent::setData('action_entity', $entity);
    }

    /**
     * Get reward update notification attribute value
     *
     * @param CustomerInterface $customer
     * @return int|null
     */
    private function getUpdateNotificationAttribute(CustomerInterface $customer): ?int
    {
        $attribute = $customer->getCustomAttribute('reward_update_notification');
        if (!$attribute) {
            return null;
        } else {
            return $attribute->getValue();
        }
    }
}
