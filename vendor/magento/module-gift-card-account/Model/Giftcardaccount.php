<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCardAccount\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\App\ObjectManager;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface;
use Magento\GiftCardAccount\Api\GiftCardRedeemerInterface;

/**
 * Gift card account model.
 *
 * Gift card accounts are balance accounts and can be used for discounts on quotes.
 *
 * @api
 * @method string getCode()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setCode(string $value)
 * @method int getStatus()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setStatus(int $value)
 * @method string getDateCreated()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setDateCreated(string $value)
 * @method string getDateExpires()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setDateExpires(string $value)
 * @method int getWebsiteId()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setWebsiteId(int $value)
 * @method float getBalance()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setBalance(float $value)
 * @method int getState()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setState(int $value)
 * @method int getIsRedeemable()
 * @method \Magento\GiftCardAccount\Model\Giftcardaccount setIsRedeemable(int $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @since 100.0.2
 */
class Giftcardaccount extends \Magento\Framework\Model\AbstractExtensibleModel implements GiftCardAccountInterface
{
    const STATUS_DISABLED = 0;

    const STATUS_ENABLED = 1;

    const STATE_AVAILABLE = 0;

    const STATE_USED = 1;

    const STATE_REDEEMED = 2;

    const STATE_EXPIRED = 3;

    const REDEEMABLE = 1;

    const NOT_REDEEMABLE = 0;

    /**#@+
     * Constants defined for keys of array
     */
    const ENTITY_ID = 'entity_id';

    const GIFT_CARDS = 'gift_cards';

    const GIFT_CARDS_AMOUNT = 'gift_cards_amount';

    const BASE_GIFT_CARDS_AMOUNT = 'base_gift_cards_amount';

    const GIFT_CARDS_AMOUNT_USED = 'gift_cards_amount_used';

    const BASE_GIFT_CARDS_AMOUNT_USED = 'base_gift_cards_amount_used';

    /**
     * Gift card id cart key
     *
     * @var string
     */
    const ID = 'i';

    /**
     * Gift card code cart key
     *
     * @var string
     */
    const CODE = 'c';

    /**
     * Gift card amount cart key
     *
     * @var string
     */
    const AMOUNT = 'a';

    /**
     * Gift card base amount cart key
     *
     * @var string
     */
    const BASE_AMOUNT = 'ba';

    /**
     * Gift card authorized cart key
     */
    const AUTHORIZED = 'authorized';

    /**#@-*/

    /**#@-*/
    protected $_eventPrefix = 'magento_giftcardaccount';

    /**
     * @var string
     */
    protected $_eventObject = 'giftcardaccount';

    /**
     * Giftcard code that was requested for load
     *
     * @var bool|string
     */
    protected $_requestedCode = false;

    /**
     * Static variable to contain codes, that were saved on previous steps in series of consecutive saves
     * Used if you use different read and write connections
     *
     * @var array
     */
    protected static $_alreadySelectedIds = [];

    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $_giftCardAccountData = null;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Customer balance balance
     *
     * @var \Magento\CustomerBalance\Model\Balance
     */
    protected $_customerBalance = null;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     * @deprecated 101.0.0 unused
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Locale\CurrencyInterface
     * @deprecated 101.0.0 unused
     */
    protected $_localeCurrency;

    /**
     * Store Manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager = null;

    /**
     * Chrckout Session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession = null;

    /**
     * Chrckout Session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession = null;

    /**
     * Chrckout Session
     *
     * @var \Magento\GiftCardAccount\Model\PoolFactory
     */
    protected $_poolFactory = null;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var EmailManagement
     */
    private $emailManagement;

    /**
     * @var GiftCardRedeemerInterface
     */
    private $redeemer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\CustomerBalance\Model\Balance $customerBalance
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\GiftCardAccount\Model\PoolFactory $poolFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param EmailManagement|null $emailManagement
     * @param GiftCardRedeemerInterface|null $redeemer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\CustomerBalance\Model\Balance $customerBalance,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\GiftCardAccount\Model\PoolFactory $poolFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        EmailManagement $emailManagement = null,
        ?GiftCardRedeemerInterface $redeemer = null
    ) {
        $this->_giftCardAccountData = $giftCardAccountData;
        $this->_scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->_customerBalance = $customerBalance;
        $this->_storeManager = $storeManager;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_localeCurrency = $localeCurrency;
        $this->_poolFactory = $poolFactory;
        $this->_localeDate = $localeDate;
        $this->quoteRepository = $quoteRepository;
        $this->emailManagement = $emailManagement;
        $this->redeemer = $redeemer ?? ObjectManager::getInstance()->get(GiftCardRedeemerInterface::class);
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Gift card ID.
     *
     * Will have value if this object is used to describe a specific entity, null if used as composed entities
     * or a new entity.
     *
     * @return int|null
     * @since 101.1.0
     */
    public function getId(): ?int
    {
        $id = parent::getId();

        return $id ? (int)$id : null;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnoreStart
     */
    public function getGiftCards()
    {
        return $this->getData(self::GIFT_CARDS);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($id)
    {
        return $this->getData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function setGiftCards(array $cards)
    {
        return $this->setData(self::GIFT_CARDS, $cards);
    }

    /**
     * @inheritDoc
     */
    public function getGiftCardsAmount()
    {
        return $this->_getData(self::GIFT_CARDS_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setGiftCardsAmount($amount)
    {
        return $this->setData(self::GIFT_CARDS_AMOUNT, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getBaseGiftCardsAmount()
    {
        return $this->_getData(self::BASE_GIFT_CARDS_AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setBaseGiftCardsAmount($amount)
    {
        return $this->setData(self::BASE_GIFT_CARDS_AMOUNT, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getGiftCardsAmountUsed()
    {
        return $this->_getData(self::GIFT_CARDS_AMOUNT_USED);
    }

    /**
     * @inheritDoc
     */
    public function setGiftCardsAmountUsed($amount)
    {
        return $this->setData(self::GIFT_CARDS_AMOUNT_USED, $amount);
    }

    /**
     * @inheritDoc
     */
    public function getBaseGiftCardsAmountUsed()
    {
        return $this->_getData(self::BASE_GIFT_CARDS_AMOUNT_USED);
    }

    /**
     * @inheritDoc
     */
    public function setBaseGiftCardsAmountUsed($amount)
    {
        return $this->setData(self::BASE_GIFT_CARDS_AMOUNT_USED, $amount);
    }

    //@codeCoverageIgnoreEnd

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\Magento\GiftCardAccount\Model\ResourceModel\Giftcardaccount::class);
    }

    /**
     * Processing object before save data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function beforeSave()
    {
        parent::beforeSave();
        $currentDate = new \DateTime('now', new \DateTimeZone($this->_localeDate->getConfigTimezone()));

        if (!$this->getId()) {
            $this->setDateCreated($currentDate);
            if (!$this->hasCode()) {
                $this->_defineCode();
            }
            $this->setIsNew(true);
        } else {
            if ($this->getOrigData('balance') != $this->getBalance()) {
                if ($this->getBalance() > 0) {
                    $this->setState(self::STATE_AVAILABLE);
                } elseif ($this->getIsRedeemable() && $this->getIsRedeemed()) {
                    $this->setState(self::STATE_REDEEMED);
                } else {
                    $this->setState(self::STATE_USED);
                }
            }
        }

        if (is_numeric($this->getLifetime()) && $this->getLifetime() > 0) {
            $this->setDateExpires(
                $this->_localeDate->date(clone $currentDate)
                    ->modify('+' . $this->getLifetime() . ' days')
                    ->format('Y-m-d')
            );
        } else {
            if ($this->getDateExpires()) {
                if ($this->isExpired()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The expiration date must be set in the future.')
                    );
                }
            } else {
                $this->setDateExpires(null);
            }
        }

        if (!$this->getId() && !$this->hasHistoryAction()) {
            $this->setHistoryAction(\Magento\GiftCardAccount\Model\History::ACTION_CREATED);
        }

        if (!$this->hasHistoryAction() && $this->getOrigData('balance') != $this->getBalance()) {
            $this->setHistoryAction(
                \Magento\GiftCardAccount\Model\History::ACTION_UPDATED
            )->setBalanceDelta(
                $this->getBalance() - $this->getOrigData('balance')
            );
        }
        if ($this->getBalance() < 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The balance cannot be less than zero.')
            );
        }
        if (((int) $this->getState() === self::STATE_EXPIRED) && $this->getBalance() > 0) {
            $this->setState(self::STATE_AVAILABLE);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {
        if ($this->getIsNew()) {
            $this->getPoolModel()->setId(
                $this->getCode()
            )->setStatus(
                \Magento\GiftCardAccount\Model\Pool\AbstractPool::STATUS_USED
            )->save();
            self::$_alreadySelectedIds[] = $this->getCode();
        }

        return parent::afterSave();
    }

    /**
     * Generate and save gift card account code
     *
     * @return \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    protected function _defineCode()
    {
        return $this->setCode($this->getPoolModel()->setExcludedIds(self::$_alreadySelectedIds)->shift());
    }

    /**
     * Load gift card account model using specified code
     *
     * @param string $code
     * @return $this
     * @deprecated 101.0.4
     * @see \Magento\GiftCardAccount\Api\GiftCardAccountRepositoryInterface
     */
    public function loadByCode($code)
    {
        $this->_requestedCode = $code;

        return $this->load($code, 'code');
    }

    /**
     * Add gift card to quote gift card storage
     *
     * @param bool $saveQuote
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToCart($saveQuote = true, $quote = null)
    {
        if ($quote === null) {
            $quote = $this->_checkoutSession->getQuote();
        }
        $website = $this->_storeManager->getStore($quote->getStoreId())->getWebsite();
        if ($this->isValid(true, true, $website)) {
            $cards = $this->_giftCardAccountData->getCards($quote);
            if (!$cards) {
                $cards = [];
            } else {
                foreach ($cards as $one) {
                    if ($one[self::ID] == $this->getId()) {
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('This gift card account is already in the quote.')
                        );
                    }
                }
            }
            $cards[] = [
                self::ID => $this->getId(),
                self::CODE => $this->getCode(),
                self::AMOUNT => $this->getBalance(),
                self::BASE_AMOUNT => $this->getBalance(),
            ];
            $this->_giftCardAccountData->setCards($quote, $cards);

            if ($saveQuote) {
                $quote->collectTotals();
                $this->quoteRepository->save($quote);
            }
        }

        return $this;
    }

    /**
     * Remove gift card from quote gift card storage
     *
     * @param bool $saveQuote
     * @param \Magento\Quote\Model\Quote|null $quote
     * @return $this|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeFromCart($saveQuote = true, $quote = null)
    {
        if (!$this->getId()) {
            $this->_throwException(__('Please correct the gift card account code: "%1".', $this->_requestedCode));
        }
        if ($quote === null) {
            $quote = $this->_checkoutSession->getQuote();
        }

        $cards = $this->_giftCardAccountData->getCards($quote);
        if ($cards) {
            foreach ($cards as $k => $one) {
                if ($one[self::ID] == $this->getId()) {
                    unset($cards[$k]);
                    $this->_giftCardAccountData->setCards($quote, $cards);

                    if ($saveQuote) {
                        $quote->collectTotals();
                        $this->quoteRepository->save($quote);
                    }
                    return $this;
                }
            }
        }

        $this->_throwException(__('This gift card account wasn\'t found in the quote.'));
    }

    /**
     * Check if this gift card is expired at the moment
     *
     * @return bool
     */
    public function isExpired()
    {
        if (!$this->getDateExpires()) {
            return false;
        }
        $timezone = $this->_localeDate->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getWebsite($this->getWebsiteId())->getDefaultStore()->getId()
        );
        $expirationDate = (new \DateTime($this->getDateExpires(), new \DateTimeZone($timezone)))->setTime(0, 0, 0);
        $currentDate = (new \DateTime('now', new \DateTimeZone($timezone)))->setTime(0, 0, 0);
        if ($expirationDate < $currentDate) {
            return true;
        }

        return false;
    }

    /**
     * Check all the gift card validity attributes
     *
     * @param bool $expirationCheck
     * @param bool $statusCheck
     * @param mixed $websiteCheck
     * @param mixed $balanceCheck
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @deprecated 101.1.0
     * @see \Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface::requestByCode()
     */
    public function isValid($expirationCheck = true, $statusCheck = true, $websiteCheck = false, $balanceCheck = true)
    {
        if (!$this->getId()) {
            $this->_throwException(
                __('Please correct the gift card account ID. Requested code: "%1"', $this->_requestedCode)
            );
        }

        if ($websiteCheck) {
            if ($websiteCheck === true) {
                $websiteCheck = null;
            }
            $website = $this->_storeManager->getWebsite($websiteCheck)->getId();
            if ($this->getWebsiteId() != $website) {
                $this->_throwException(__('Please correct the gift card account website: %1.', $this->getWebsiteId()));
            }
        }

        if ($statusCheck && $this->getStatus() != self::STATUS_ENABLED) {
            $this->_throwException(__('Gift card account %1 is not enabled.', $this->getId()));
        }

        if ($expirationCheck && (((int) $this->getState()) === self::STATE_EXPIRED || $this->isExpired())) {
            $this->_throwException(__('Gift card account %1 is expired.', $this->getId()));
        }

        if ($balanceCheck) {
            if ($this->getBalance() <= 0) {
                $this->_throwException(__('Gift card account %1 has a zero balance.', $this->getId()));
            }
            if ($balanceCheck !== true && is_numeric($balanceCheck)) {
                if ($this->getBalance() < $balanceCheck) {
                    $this->_throwException(
                        __('Gift card account %1 balance is lower than the charged amount.', $this->getId())
                    );
                }
            }
        }

        return true;
    }

    /**
     * Reduce Gift Card Account balance by specified amount
     *
     * @param float $amount
     * @return $this
     */
    public function charge($amount)
    {
        if ($this->isValid(false, false, false, $amount)) {
            $this->setBalanceDelta(
                -$amount
            )->setBalance(
                $this->getBalance() - $amount
            )->setHistoryAction(
                \Magento\GiftCardAccount\Model\History::ACTION_USED
            );
        }

        return $this;
    }

    /**
     * Revert amount to gift card balance if order was not placed
     *
     * @param   float $amount
     * @return  $this
     */
    public function revert($amount)
    {
        $amount = (double)$amount;

        if ($amount > 0 && $this->isValid(true, true, false, false)) {
            $this->setBalanceDelta(
                $amount
            )->setBalance(
                $this->getBalance() + $amount
            )->setHistoryAction(
                \Magento\GiftCardAccount\Model\History::ACTION_UPDATED
            );
        }

        return $this;
    }

    /**
     * Set state text on after load
     *
     * @return $this
     */
    public function _afterLoad()
    {
        $this->_setStateText();
        return parent::_afterLoad();
    }

    /**
     * Return Gift Card Account state options
     *
     * @return array
     */
    public function getStatesAsOptionList()
    {
        $result = [];

        $result[self::STATE_AVAILABLE] = __('Available');
        $result[self::STATE_USED] = __('Used');
        $result[self::STATE_REDEEMED] = __('Redeemed');
        $result[self::STATE_EXPIRED] = __('Expired');

        return $result;
    }

    /**
     * Retrieve pool model instance
     *
     * @return \Magento\GiftCardAccount\Model\Pool\AbstractPool
     */
    public function getPoolModel()
    {
        return $this->_poolFactory->create();
    }

    /**
     * Update gift card accounts state
     *
     * @param array $ids
     * @param int $state
     * @return $this
     */
    public function updateState($ids, $state)
    {
        if ($ids) {
            $this->getResource()->updateState($ids, $state);
        }
        return $this;
    }

    /**
     * Redeem gift card (-gca balance, +cb balance)
     *
     * @param int $customerId
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @deprecated 101.1.1 API for this has been introduced.
     * @see \Magento\GiftcardAccount\Api\GiftCardRedeemerInterface::redeem()
     */
    public function redeem($customerId = null)
    {
        if ($customerId === null) {
            $customerId = $this->_customerSession->getCustomerId();
        }
        if (!$customerId) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please enter a valid customer ID.'));
        }
        $this->redeemer->redeem($this->getCode(), $customerId);

        return $this;
    }

    /**
     * Send email.
     *
     * @return void
     * @deprecated 101.0.0 use EmailManagement::sendEmail() instead
     */
    public function sendEmail()
    {
        $result = $this->emailManagement->sendEmail($this);
        $this->setEmailSent($result);
    }

    /**
     * Set state text by loaded state code.
     *
     * Used in _afterLoad().
     *
     * @return string
     */
    protected function _setStateText()
    {
        $states = $this->getStatesAsOptionList();

        if (isset($states[$this->getState()])) {
            $stateText = $states[$this->getState()];
            $this->setStateText($stateText);
            return $stateText;
        }
        return '';
    }

    /**
     * Obscure real exception message to prevent brute force attacks
     *
     * @param string $realMessage
     * @param string $fakeMessage
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _throwException($realMessage, $fakeMessage = '')
    {
        $this->_logger->critical(new \Magento\Framework\Exception\LocalizedException(__($realMessage)));
        if (!$fakeMessage) {
            $fakeMessage = 'Please correct the gift card code.';
        }
        throw new \Magento\Framework\Exception\LocalizedException(__($fakeMessage));
    }

    /**
     * @inheritDoc
     *
     * @return \Magento\GiftCardAccount\Api\Data\GiftCardAccountExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritDoc
     *
     * @param \Magento\GiftCardAccount\Api\Data\GiftCardAccountExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\GiftCardAccount\Api\Data\GiftCardAccountExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
