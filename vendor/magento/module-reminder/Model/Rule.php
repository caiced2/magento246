<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model;

/**
 * Reminder Rule data model
 *
 * @method string getName()
 * @method \Magento\Reminder\Model\Rule setName(string $value)
 * @method string getDescription()
 * @method \Magento\Reminder\Model\Rule setDescription(string $value)
 * @method string getConditionsSerialized()
 * @method \Magento\Reminder\Model\Rule setConditionsSerialized(string $value)
 * @method string getConditionSql()
 * @method \Magento\Reminder\Model\Rule setConditionSql(string $value)
 * @method int getIsActive()
 * @method \Magento\Reminder\Model\Rule setIsActive(int $value)
 * @method int getSalesruleId()
 * @method \Magento\Reminder\Model\Rule setSalesruleId(int $value)
 * @method string getSchedule()
 * @method \Magento\Reminder\Model\Rule setSchedule(string $value)
 * @method string getDefaultLabel()
 * @method \Magento\Reminder\Model\Rule setDefaultLabel(string $value)
 * @method string getDefaultDescription()
 * @method \Magento\Reminder\Model\Rule setDefaultDescription(string $value)
 * @method \Magento\Reminder\Model\Rule setActiveFrom(string $value)
 * @method \Magento\Reminder\Model\Rule setActiveTo(string $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Rule extends \Magento\Rule\Model\AbstractModel
{
    const XML_PATH_EMAIL_TEMPLATE = 'magento_reminder_email_template';

    /**
     * Store template data defined per store view, will be used in email templates as variables
     *
     * @var array
     */
    protected $_storeData = [];

    /**
     * Reminder data
     *
     * @var \Magento\Reminder\Helper\Data
     */
    protected $_reminderData = null;

    /**
     * @var Rule\Condition\Combine\RootFactory
     */
    protected $rootFactory;

    /**
     * @var \Magento\Rule\Model\Action\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTimeFactory
     */
    protected $dateFactory;

    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $salesRule;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Quote\Model\QueryResolver
     */
    protected $queryResolver;

    /**
     * Rule constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param Rule\Condition\Combine\RootFactory $rootFactory
     * @param \Magento\Rule\Model\Action\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\SalesRule\Model\CouponFactory $couponFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory
     * @param \Magento\SalesRule\Model\Rule $salesRule
     * @param \Magento\Reminder\Helper\Data $reminderData
     * @param ResourceModel\Rule $resource
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Quote\Model\QueryResolver $queryResolver
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Reminder\Model\Rule\Condition\Combine\RootFactory $rootFactory,
        \Magento\Rule\Model\Action\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Framework\Stdlib\DateTime\DateTimeFactory $dateFactory,
        \Magento\SalesRule\Model\Rule $salesRule,
        \Magento\Reminder\Helper\Data $reminderData,
        \Magento\Reminder\Model\ResourceModel\Rule $resource,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Quote\Model\QueryResolver $queryResolver,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->rootFactory = $rootFactory;
        $this->collectionFactory = $collectionFactory;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->couponFactory = $couponFactory;
        $this->dateFactory = $dateFactory;
        $this->salesRule = $salesRule;
        $this->_reminderData = $reminderData;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->queryResolver = $queryResolver;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magento\Reminder\Model\ResourceModel\Rule::class);
    }

    /**
     * Set template, label and description data per store
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();

        $storeData = $this->_getResource()->getStoreData($this->getId());
        $defaultTemplate = self::XML_PATH_EMAIL_TEMPLATE;

        foreach ($storeData as $data) {
            $template = empty($data['template_id']) ? $defaultTemplate : $data['template_id'];
            $this->setData('store_template_' . $data['store_id'], $template);
            $this->setData('store_label_' . $data['store_id'], $data['label']);
            $this->setData('store_description_' . $data['store_id'], $data['description']);
        }

        return $this;
    }

    /**
     * Set aggregated conditions SQL and reset sales rule Id if applicable
     *
     * @return $this
     */
    public function beforeSave()
    {
        $this->setConditionSql($this->getConditions()->getConditionsSql(null, new \Zend_Db_Expr(':website_id')));

        if (!$this->getSalesruleId()) {
            $this->setSalesruleId(null);
        }

        parent::beforeSave();
        return $this;
    }

    /**
     * Getter for rule combine conditions instance
     *
     * @return \Magento\Reminder\Model\Rule\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->rootFactory->create();
    }

    /**
     * Getter for rule actions collection instance
     *
     * @return \Magento\Rule\Model\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->collectionFactory->create();
    }

    /**
     * Send reminder emails
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function sendReminderEmails()
    {
        $this->inlineTranslation->suspend();

        $identity = $this->_reminderData->getEmailIdentity();

        $this->_matchCustomers();
        $limit = $this->_reminderData->getOneRunLimit();

        $recipients = $this->_getResource()->getCustomersForNotification($limit, $this->getRuleId());

        foreach ($recipients as $recipient) {
            /* @var $customer \Magento\Customer\Model\Customer */
            $customer = $this->customerFactory->create()->load($recipient['customer_id']);
            if (!$customer || !$customer->getId()) {
                continue;
            }

            if ($customer->getStoreId()) {
                $store = $customer->getStore();
            } else {
                $store = $this->storeManager->getWebsite($customer->getWebsiteId())->getDefaultStore();
            }

            $storeData = $this->getStoreData($recipient['rule_id'], $store->getId());
            if (!$storeData) {
                continue;
            }

            /* @var $coupon \Magento\SalesRule\Model\Coupon */
            $coupon = $this->couponFactory->create()->load($recipient['coupon_id']);

            $templateVars = [
                'store' => $store,
                'coupon' => $coupon,
                'customer' => $customer,
                'customer_data' => [
                    'name' => $customer->getName(),
                ],
                'promotion_name' => $storeData['label'] ?: $this->getDefaultLabel(),
                'promotion_description' => $storeData['description'] ?: $this->getDefaultDescription(),
            ];

            $transport = $this->_transportBuilder->setTemplateIdentifier(
                $storeData['template_id']
            )->setTemplateOptions(
                ['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $store->getId()]
            )->setTemplateVars(
                $templateVars
            )->setFrom(
                $identity
            )->addTo(
                $customer->getEmail()
            )->getTransport();

            try {
                $transport->sendMessage();
                $this->_getResource()->addNotificationLog($recipient['rule_id'], $customer->getId());
            } catch (\Magento\Framework\Exception\MailException $e) {
                $this->_getResource()->updateFailedEmailsCounter($recipient['rule_id'], $customer->getId());
            }
        }

        $this->inlineTranslation->resume();

        return $this;
    }

    /**
     * Match customers for current rule and assign coupons
     *
     * @return $this
     */
    protected function _matchCustomers()
    {
        $threshold = $this->_reminderData->getSendFailureThreshold();
        $currentDate = $this->dateFactory->create()->date('Y-m-d H:i:s');
        $rules = $this->getCollection()->addDateFilter($currentDate)->addIsActiveFilter(1);
        if ($this->getRuleId()) {
            $rules->addRuleFilter($this->getRuleId());
        }

        foreach ($rules as $rule) {
            $this->_getResource()->deactivateMatchedCustomers($rule->getId());

            if ($rule->getSalesruleId()) {
                /* @var $salesRule \Magento\SalesRule\Model\Rule */
                $salesRule = $this->salesRule->load($rule->getSalesruleId());
                $websiteIds = array_intersect($rule->getWebsiteIds(), $salesRule->getWebsiteIds());
            } else {
                $salesRule = null;
                $websiteIds = $rule->getWebsiteIds();
            }

            if ($this->queryResolver->isSingleQuery()) {
                foreach ($websiteIds as $websiteId) {
                    $this->_getResource()->saveMatchedCustomers($rule, $salesRule, $websiteId, $threshold);
                }
            } else {
                $this->processCondition($rule, $salesRule, $websiteIds, $threshold);
            }
        }
        return $this;
    }

    /**
     * Process rule condition
     *
     * @param \Magento\Reminder\Model\Rule $rule
     * @param \Magento\SalesRule\Model\Rule $salesRule
     * @param array $websiteIds
     * @param int|null $threshold
     * @return $this
     */
    protected function processCondition($rule, $salesRule, array $websiteIds, $threshold = null)
    {
        $rule->afterLoad();
        $relatedCustomers = [];
        foreach ($websiteIds as $websiteId) {
            //get customers ids that satisfy conditions
            $customers = $rule->getConditions()->getSatisfiedIds($websiteId, $rule->getId(), $threshold);
            foreach ($customers as $customer) {
                $relatedCustomers[] = [
                    'entity_id' => $customer['entity_id'],
                    'coupon_id' => $customer['coupon_id'],
                    'website_id' => $websiteId,
                ];
            }
        }
        $this->_getResource()->saveSatisfiedCustomers($relatedCustomers, $rule, $salesRule);
        return $this;
    }

    /**
     * Retrieve store template data
     *
     * @param int $ruleId
     * @param int $storeId
     * @return array|false
     */
    public function getStoreData($ruleId, $storeId)
    {
        if (!isset($this->_storeData[$ruleId][$storeId])) {
            if ($data = $this->_getResource()->getStoreTemplateData($ruleId, $storeId)) {
                if (empty($data['template_id'])) {
                    $data['template_id'] = self::XML_PATH_EMAIL_TEMPLATE;
                }
                $this->_storeData[$ruleId][$storeId] = $data;
            } else {
                return false;
            }
        }

        return $this->_storeData[$ruleId][$storeId];
    }

    /**
     * Detaches Sales Rule from all Email Remainder Rules that uses it
     *
     * @param int $salesRuleId
     * @return $this
     */
    public function detachSalesRule($salesRuleId)
    {
        $this->getResource()->detachSalesRule($salesRuleId);
        return $this;
    }

    /**
     * Retrieve active from date.
     *
     * Implemented for backwards compatibility with old property called "active_from"
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getActiveFrom()
    {
        return $this->getData('from_date');
    }

    /**
     * Retrieve active to date.
     *
     * Implemented for backwards compatibility with old property called "active_to"
     *
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getActiveTo()
    {
        return $this->getData('to_date');
    }
}
