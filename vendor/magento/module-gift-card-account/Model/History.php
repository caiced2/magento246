<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Gift cart account history model
 *
 * @method int getGiftcardaccountId()
 * @method History setGiftcardaccountId(int $value)
 * @method string getUpdatedAt()
 * @method History setUpdatedAt(string $value)
 * @method int getAction()
 * @method History setAction(int $value)
 * @method float getBalanceAmount()
 * @method History setBalanceAmount(float $value)
 * @method float getBalanceDelta()
 * @method History setBalanceDelta(float $value)
 * @method string getAdditionalInfo()
 * @method History setAdditionalInfo(string $value)
 *
 * @author Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class History extends AbstractModel
{
    const ACTION_CREATED = 0;

    const ACTION_USED = 1;

    const ACTION_SENT = 2;

    const ACTION_REDEEMED = 3;

    const ACTION_EXPIRED = 4;

    const ACTION_UPDATED = 5;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Session|null
     */
    private $checkoutSession;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     * @param Session|null $checkoutSession
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = [],
        Session $checkoutSession = null
    ) {
        $this->_storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession ?? ObjectManager::getInstance()
                ->get(Session::class);
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\History::class);
    }

    /**
     * Get action names array
     *
     * @return array
     */
    public function getActionNamesArray()
    {
        return [
            self::ACTION_CREATED => __('Created'),
            self::ACTION_UPDATED => __('Updated'),
            self::ACTION_SENT => __('Sent'),
            self::ACTION_USED => __('Used'),
            self::ACTION_REDEEMED => __('Redeemed'),
            self::ACTION_EXPIRED => __('Expired')
        ];
    }

    /**
     * Get info about creation context
     *
     * @return Phrase|string
     */
    protected function _getCreatedAdditionalInfo()
    {
        $orderId = null;
        if ($this->getGiftcardaccount()->getOrder()) {
            $orderId = $this->getGiftcardaccount()
                ->getOrder()
                ->getIncrementId();
        } elseif ($this->checkoutSession->getQuote() && $this->checkoutSession->getQuote()->getReservedOrderId()) {
            $orderId = $this->checkoutSession->getQuote()
                ->getReservedOrderId();
        }

        return $orderId ? __('Order #%1.', $orderId) : '';
    }

    /**
     * Get used additional info
     *
     * @return Phrase|string
     */
    protected function _getUsedAdditionalInfo()
    {
        if ($this->getGiftcardaccount()->getOrder()) {
            $orderId = $this->getGiftcardaccount()->getOrder()->getIncrementId();
            return __('Order #%1.', $orderId);
        }

        return '';
    }

    /**
     * Get info about sent mail context
     *
     * @return Phrase
     */
    protected function _getSentAdditionalInfo()
    {
        $recipient = $this->getGiftcardaccount()->getRecipientEmail();
        $name = $this->getGiftcardaccount()->getRecipientName();
        if ($name) {
            $recipient = "{$name} <{$recipient}>";
        }

        return __('Recipient: %1.', $recipient);
    }

    /**
     * Get redeemed additional info
     *
     * @return Phrase|string
     */
    protected function _getRedeemedAdditionalInfo()
    {
        if ($customerId = $this->getGiftcardaccount()->getCustomerId()) {
            return __('Customer #%1.', $customerId);
        }
        return '';
    }

    /**
     * Get info about update context
     *
     * @return string
     */
    protected function _getUpdatedAdditionalInfo()
    {
        return '';
    }

    /**
     * Get expired additional info
     *
     * @return string
     */
    protected function _getExpiredAdditionalInfo()
    {
        return '';
    }

    /**
     * Processing object before save data
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        if (!$this->hasGiftcardaccount()) {
            throw new LocalizedException(__('Please assign a gift card account.'));
        }

        $this->setAction($this->getGiftcardaccount()->getHistoryAction());
        $this->setGiftcardaccountId($this->getGiftcardaccount()->getId());
        $this->setBalanceAmount($this->getGiftcardaccount()->getBalance());
        $this->setBalanceDelta($this->getGiftcardaccount()->getBalanceDelta());

        switch ($this->getGiftcardaccount()->getHistoryAction()) {
            case self::ACTION_CREATED:
                $this->setAdditionalInfo($this->_getCreatedAdditionalInfo());

                $this->setBalanceDelta($this->getBalanceAmount());
                break;
            case self::ACTION_USED:
                $this->setAdditionalInfo($this->_getUsedAdditionalInfo());
                break;
            case self::ACTION_SENT:
                $this->setAdditionalInfo($this->_getSentAdditionalInfo());
                break;
            case self::ACTION_REDEEMED:
                $this->setAdditionalInfo($this->_getRedeemedAdditionalInfo());
                break;
            case self::ACTION_UPDATED:
                $this->setAdditionalInfo($this->_getUpdatedAdditionalInfo());
                break;
            case self::ACTION_EXPIRED:
                $this->setAdditionalInfo($this->_getExpiredAdditionalInfo());
                break;
            default:
                throw new LocalizedException(__('Unknown history action.'));
        }

        return parent::beforeSave();
    }

    /**
     * @inheritdoc
     */
    public function hasDataChanges()
    {
        return parent::hasDataChanges()
            || ($this->hasGiftcardaccount() && $this->getGiftcardaccount()->hasDataChanges());
    }
}
