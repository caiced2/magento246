<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Invitation\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\ValidatorChain;

/**
 * Invitation data model
 *
 * @api
 * @method int getCustomerId()
 * @method \Magento\Invitation\Model\Invitation setCustomerId(int $value)
 * @method string getInvitationDate()
 * @method \Magento\Invitation\Model\Invitation setInvitationDate(string $value)
 * @method string getEmail()
 * @method \Magento\Invitation\Model\Invitation setEmail(string $value)
 * @method int getReferralId()
 * @method \Magento\Invitation\Model\Invitation setReferralId(int $value)
 * @method string getProtectionCode()
 * @method \Magento\Invitation\Model\Invitation setProtectionCode(string $value)
 * @method string getSignupDate()
 * @method \Magento\Invitation\Model\Invitation setSignupDate(string $value)
 * @method \Magento\Invitation\Model\Invitation setStoreId(int $value)
 * @method int getGroupId()
 * @method \Magento\Invitation\Model\Invitation setGroupId(int $value)
 * @method string getMessage()
 * @method \Magento\Invitation\Model\Invitation setMessage(string $value)
 * @method string getStatus()
 * @method \Magento\Invitation\Model\Invitation setStatus(string $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Invitation extends \Magento\Framework\Model\AbstractModel
{
    public const XML_PATH_EMAIL_IDENTITY = 'magento_invitation/email/identity';

    public const XML_PATH_EMAIL_TEMPLATE = 'magento_invitation/email/template';

    /**
     * @var array
     */
    private static $_customerExistsLookup = [];

    /**
     * @var string
     */
    protected $_eventPrefix = 'magento_invitation';

    /**
     * @var string
     */
    protected $_eventObject = 'invitation';

    /**
     * @var \Magento\Invitation\Helper\Data
     */
    protected $_invitationData;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Invitation Config
     *
     * @var \Magento\Invitation\Model\Config
     */
    protected $_config;

    /**
     * Invitation History Factory
     *
     * @var \Magento\Invitation\Model\Invitation\HistoryFactory
     */
    protected $_historyFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var Invitation\Status
     */
    protected $status;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Invitation\Helper\Data $invitationData
     * @param \Magento\Invitation\Model\ResourceModel\Invitation $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Invitation\Model\Config $config
     * @param \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Invitation\Status $status
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Invitation\Helper\Data $invitationData,
        \Magento\Invitation\Model\ResourceModel\Invitation $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Invitation\Model\Config $config,
        \Magento\Invitation\Model\Invitation\HistoryFactory $historyFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Invitation\Status $status,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_invitationData = $invitationData;
        $this->_storeManager = $storeManager;
        $this->_config = $config;
        $this->_historyFactory = $historyFactory;
        $this->_customerFactory = $customerFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->mathRandom = $mathRandom;
        $this->dateTime = $dateTime;
        $this->_scopeConfig = $scopeConfig;
        $this->status = $status;
    }

    /**
     * Method to get customer repository.
     *
     * @return \Magento\Customer\Api\CustomerRepositoryInterface|mixed
     *
     * @deprecated 100.1.0
     * @see we don't recommend this approach anymore
     */
    private function getCustomerRepository()
    {
        if ($this->customerRepository === null) {
            $this->customerRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Customer\Api\CustomerRepositoryInterface::class);
        }

        return $this->customerRepository;
    }

    /**
     * Intialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Invitation\Model\ResourceModel\Invitation::class);
    }

    /**
     * Store ID getter
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->hasData('store_id')) {
            return $this->_getData('store_id');
        }
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Load invitation by an encrypted code
     *
     * @param string $code
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByInvitationCode($code)
    {
        $code = explode(':', $code, 2);
        if (count($code) != 2) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The invitation code is incorrect. Verify the code and try again.')
            );
        }
        list($id, $protectionCode) = $code;
        $this->load($id);
        if (!$this->getId() || $this->getProtectionCode() != $protectionCode) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The invitation code is incorrect. Verify the code and try again.')
            );
        }
        return $this;
    }

    /**
     * Model before save
     *
     * @return $this
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function beforeSave()
    {
        if (!$this->getId()) {
            // set initial data for new one
            $this->addData(
                [
                    'protection_code' => $this->mathRandom->getUniqueHash(),
                    'status' => Invitation\Status::STATUS_NEW,
                    'invitation_date' => $this->dateTime->formatDate(time()),
                    'store_id' => $this->getStoreId(),
                ]
            );
            $inviter = $this->getInviter();
            if ($inviter) {
                $this->setCustomerId($inviter->getId());
            }
            if ($this->_config->getUseInviterGroup()) {
                if ($inviter) {
                    $this->setGroupId($inviter->getGroupId());
                }
                if (!$this->hasGroupId()) {
                    throw new \Magento\Framework\Exception\InputException(
                        __('A customer ID group is missing. Enter a group and try again.')
                    );
                }
            } else {
                $this->unsetData('group_id');
            }

            if (!(int)$this->getStoreId()) {
                throw new \Magento\Framework\Exception\InputException(
                    __('The incorrect store is specified. Verify the store and try again.')
                );
            }
            $this->makeSureCustomerNotExists();
        } else {
            if ($this->dataHasChangedFor('message') && !$this->canMessageBeUpdated()) {
                throw new \Magento\Framework\Exception\InputException(
                    __(
                        'The store credit entered was invalid. Store credit must be less than the order amount. '
                        . 'Verify and try again.'
                    )
                );
            }
        }
        return parent::beforeSave();
    }

    /**
     * Update status history after save
     *
     * @return $this
     */
    public function afterSave()
    {
        $this->_historyFactory->create()->setInvitationId($this->getId())->setStatus($this->getStatus())->save();
        $parent = parent::afterSave();
        if ($this->getStatus() === Invitation\Status::STATUS_NEW) {
            $this->setOrigData();
        }
        return $parent;
    }

    /**
     * Send invitation email
     *
     * @return bool
     */
    public function sendInvitationEmail()
    {
        $this->makeSureCanBeSent();
        $store = $this->_storeManager->getStore($this->getStoreId());

        $templateIdentifier = $this->_scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE,
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
            ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->getStoreId()]
        )->setTemplateVars(
            [
                'url' => $this->_invitationData->getInvitationUrl($this),
                'message' => $this->getMessage(),
                'store' => $store,
                'store_name' => $store->getGroup()->getName(),
                'inviter_name' => $this->getInviter() ? $this->getInviter()->getName() : null,
            ]
        )->setFrom(
            $from
        )->addTo(
            $this->getEmail()
        );
        $transport = $this->_transportBuilder->getTransport();
        try {
            $transport->sendMessage();
        } catch (\Magento\Framework\Exception\MailException $e) {
            return false;
        }
        $this->setStatus(Invitation\Status::STATUS_SENT)->setUpdateDate(true)->save();
        return true;
    }

    /**
     * Get an encrypted invitation code
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInvitationCode()
    {
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(__("The encrypted code couldn't be generated."));
        }
        return $this->getId() . ':' . $this->getProtectionCode();
    }

    /**
     * Check and get customer if it was set
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getInviter()
    {
        $inviter = $this->getCustomer();
        if (!$inviter || !$inviter->getId()) {
            $inviter = null;
        }
        return $inviter;
    }

    /**
     * Check whether invitation can be sent. Will throw exception on invalid data.
     *
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     */
    public function makeSureCanBeSent()
    {
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\InputException(
                __("An ID wasn't found for this invitation. Verify the invitation and try again.")
            );
        }
        if (!in_array($this->getStatus(), $this->status->getCanBeSentStatuses())) {
            throw new \Magento\Framework\Exception\InputException(
                __('The invitation with the "%1" status can\'t be sent.', $this->getStatus())
            );
        }
        if (!$this->getEmail() || !ValidatorChain::is($this->getEmail(), EmailAddress::class)) {
            throw new \Magento\Framework\Exception\InputException(
                __('A valid invitation email is missing. Enter and try again.')
            );
        }
        $this->makeSureCustomerNotExists();
    }

    /**
     * Check whether customer with specified email exists
     *
     * @param string $email
     * @param string $websiteId
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\InputException
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function makeSureCustomerNotExists($email = null, $websiteId = null)
    {
        if (null === $websiteId) {
            $websiteId = $this->_storeManager->getStore($this->getStoreId())->getWebsiteId();
        }
        if (!$websiteId) {
            throw new \Magento\Framework\Exception\InputException(__("The proper website couldn't be identified"));
        }
        if (null === $email) {
            $email = $this->getEmail();
        }
        if (!$email) {
            throw new \Magento\Framework\Exception\InputException(
                __("An email wasn't specified. Enter the email and try again.")
            );
        }

        // lookup customer by specified email/website id
        if (!isset(self::$_customerExistsLookup[$email]) || !isset(self::$_customerExistsLookup[$email][$websiteId])) {
            $customer = $this->_customerFactory->create()->setWebsiteId($websiteId)->loadByEmail($email);
            self::$_customerExistsLookup[$email][$websiteId] = $customer->getId() ? $customer->getId() : false;
        }
        if (false === self::$_customerExistsLookup[$email][$websiteId]) {
            return;
        }
        throw new \Magento\Framework\Exception\AlreadyExistsException(
            __('This invitation is addressed to current customer "%1".', $email)
        );
    }

    /**
     * Check whether this invitation can be accepted
     *
     * @param int|string $websiteId
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    public function makeSureCanBeAccepted($websiteId = null)
    {
        $messageInvalid = __('This invitation is not valid.');
        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
        if (!in_array($this->getStatus(), $this->status->getCanBeAcceptedStatuses())) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
        if (null === $websiteId) {
            $websiteId = $this->_storeManager->getWebsite()->getId();
        }
        if ($websiteId != $this->_storeManager->getStore($this->getStoreId())->getWebsiteId()) {
            throw new \Magento\Framework\Exception\InputException($messageInvalid);
        }
    }

    /**
     * Check whether message can be updated
     *
     * @return bool
     */
    public function canMessageBeUpdated()
    {
        return (bool)(int)$this->getId() && $this->getStatus() === Invitation\Status::STATUS_NEW;
    }

    /**
     * Check whether invitation can be cancelled
     *
     * @return bool
     */
    public function canBeCanceled()
    {
        return (bool)(int)$this->getId() && in_array($this->getStatus(), $this->status->getCanBeCancelledStatuses());
    }

    /**
     * Check whether invitation can be sent.
     *
     * @return bool
     */
    public function canBeSent()
    {
        try {
            $this->makeSureCanBeSent();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Cancel the invitation
     *
     * @return $this
     */
    public function cancel()
    {
        if ($this->canBeCanceled()) {
            $this->setStatus(Invitation\Status::STATUS_CANCELED)->save();
        }
        return $this;
    }

    /**
     * Accept the invitation
     *
     * @param int|string $websiteId
     * @param int $referralId
     * @return $this
     */
    public function accept($websiteId, $referralId)
    {
        $this->makeSureCanBeAccepted($websiteId);

        $this->getResource()->beginTransaction();
        try {
            $this->setReferralId(
                $referralId
            )->setStatus(
                Invitation\Status::STATUS_ACCEPTED
            )->setSignupDate(
                $this->dateTime->formatDate(time())
            )->save();
            $inviterId = $this->getCustomerId();
            if ($inviterId) {
                $this->getResource()->trackReferral($inviterId, $referralId);
            }
            $this->assignInvitationDataToCustomer($referralId);
            $this->getResource()->commit();
        } catch (\Exception $e) {
            $this->getResource()->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Check whether invitation can be accepted
     *
     * @param int $websiteId
     * @return bool
     */
    public function canBeAccepted($websiteId = null)
    {
        try {
            $this->makeSureCanBeAccepted($websiteId);
            return true;
            // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
        }
        return false;
    }

    /**
     * Validating invitation's parameters
     *
     * Returns true or array of errors
     *
     * @return string[]|bool
     */
    public function validate()
    {
        $errors = [];

        if (!ValidatorChain::is($this->getEmail(), EmailAddress::class)) {
            $errors[] = __('Please correct the invitation email.');
        }

        if (!empty($errors)) {
            return $errors;
        }

        return true;
    }

    /**
     * Method to assign invitation data to customer.
     *
     * @param int $customerId
     * @throws NoSuchEntityException
     * @return void
     * @since 100.1.0
     */
    public function assignInvitationDataToCustomer($customerId)
    {
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $this->getCustomerRepository()->getById($customerId);

        if ($customer->getId() == $customerId) {
            $customer->setGroupId($this->getGroupId());
            $this->getCustomerRepository()->save($customer);
        } else {
            throw new NoSuchEntityException();
        }
    }
}
