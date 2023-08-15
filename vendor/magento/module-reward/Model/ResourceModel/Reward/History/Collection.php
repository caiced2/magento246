<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Model\ResourceModel\Reward\History;

/**
 * Reward history collection
 *
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * The expiry config list
     *
     * @var array
     */
    protected $_expiryConfig = [];

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Date time formatter
     *
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        $this->_localeResolver = $localeResolver;
        $this->_customerFactory = $customerFactory;
        $this->dateTime = $dateTime;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    /**
     * Internal constructor
     *
     * @return void
     * @codeCoverageIgnore
     */
    protected function _construct()
    {
        $this->_init(
            \Magento\Reward\Model\Reward\History::class,
            \Magento\Reward\Model\ResourceModel\Reward\History::class
        );
    }

    /**
     * Unserialize fields of each loaded collection item
     *
     * {@inheritdoc}
     */
    protected function _afterLoad()
    {
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }
        return parent::_afterLoad();
    }

    /**
     * Join reward table and retrieve total balance total with customer_id
     *
     * @return $this
     */
    protected function _joinReward()
    {
        if ($this->getFlag('reward_joined')) {
            return $this;
        }
        $this->getSelect()->joinInner(
            ['reward_table' => $this->getTable('magento_reward')],
            'reward_table.reward_id = main_table.reward_id',
            ['customer_id', 'points_balance_total' => 'points_balance']
        );
        $this->setFlag('reward_joined', true);
        return $this;
    }

    /**
     * Getter for $_expiryConfig
     *
     * @param int $websiteId Specified Website Id
     * @return array|\Magento\Framework\DataObject
     */
    protected function _getExpiryConfig($websiteId = null)
    {
        if ($websiteId !== null && isset($this->_expiryConfig[$websiteId])) {
            return $this->_expiryConfig[$websiteId];
        }
        return $this->_expiryConfig;
    }

    /**
     * Setter for $_expiryConfig
     *
     * @param array $config
     * @return $this
     */
    public function setExpiryConfig($config)
    {
        if (!is_array($config)) {
            return $this;
        }
        $this->_expiryConfig = $config;
        return $this;
    }

    /**
     * Join reward table to filter history by customer id
     *
     * @param string $customerId
     * @return $this
     */
    public function addCustomerFilter($customerId)
    {
        if ($customerId) {
            $this->_joinReward();
            $this->getSelect()->where('reward_table.customer_id = ?', $customerId);
        }
        return $this;
    }

    /**
     * Skip Expired duplicates records (with action = -1)
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function skipExpiredDuplicates()
    {
        $this->getSelect()->where('main_table.is_duplicate_of IS NULL');
        return $this;
    }

    /**
     * Add filter by website id
     *
     * @param int|array $websiteId
     * @return $this
     * @codeCoverageIgnore
     */
    public function addWebsiteFilter($websiteId)
    {
        $this->getSelect()->where(
            is_array($websiteId) ? 'main_table.website_id IN (?)' : 'main_table.website_id = ?',
            $websiteId
        );
        return $this;
    }

    /**
     * Join additional customer information, such as email, name etc.
     *
     * @return $this
     */
    public function addCustomerInfo()
    {
        if ($this->getFlag('customer_added')) {
            return $this;
        }

        $this->_joinReward();

        $customer = $this->_customerFactory->create();
        /* @var $customer \Magento\Customer\Model\Customer */
        $warningNotification = $customer->getAttribute('reward_warning_notification');

        $connection = $this->getConnection();
        /* @var $connection \Magento\Framework\DB\Adapter\AdapterInterface */

        $this->getSelect()->joinInner(
            ['customer' => $customer->getEntityType()->getEntityTable()],
            'customer.entity_id=reward_table.customer_id',
            [
                'customer_email' => 'email',
                'customer_group_id' => 'group_id',
                'customer_lastname' => 'lastname',
                'customer_firstname' => 'firstname'
            ]
        )->joinLeft(
            ['warning_notification' => $warningNotification->getBackend()->getTable()],
            $connection->quoteInto(
                'warning_notification.entity_id=reward_table.customer_id AND warning_notification.attribute_id = ?',
                $warningNotification->getAttributeId()
            ),
            ['reward_warning_notification' => 'value']
        );

        $this->setFlag('customer_added', true);
        return $this;
    }

    /**
     * Add correction to expiration date based on expiry calculation
     *
     * CASE ... WHEN ... THEN is used only in admin area to show expiration date for all stores
     *
     * @param int $websiteId
     *
     * @return $this
     */
    public function addExpirationDate($websiteId = null)
    {
        $expiryConfig = $this->_getExpiryConfig($websiteId);
        $connection = $this->getConnection();
        if (!$expiryConfig) {
            return $this;
        }

        if ($websiteId !== null) {
            $field = $expiryConfig->getExpiryCalculation() == 'static' ? 'expired_at_static' : 'expired_at_dynamic';
            $this->getSelect()->columns(['expiration_date' => $field]);
        } else {
            $cases = [];
            foreach ($expiryConfig as $wId => $config) {
                $field = $config->getExpiryCalculation() == 'static' ? 'expired_at_static' : 'expired_at_dynamic';
                $cases[$wId] = $field;
            }

            if (count($cases) > 0) {
                $sql = $connection->getCaseSql('main_table.website_id', $cases);
                $this->getSelect()->columns(['expiration_date' => new \Zend_Db_Expr($sql)]);
            }
        }

        return $this;
    }

    /**
     * Return total amounts of points that will be expired soon (pre-configured days value) for specified website
     *
     * Result is grouped by customer
     *
     * @param int $websiteId Specified Website
     * @param bool $subscribedOnly Whether to load expired soon points only for subscribed customers
     *
     * @return $this
     */
    public function loadExpiredSoonPoints($websiteId, $subscribedOnly = true)
    {
        $expiryConfig = $this->_getExpiryConfig($websiteId);
        if (!$expiryConfig) {
            return $this;
        }
        $inDays = (int)$expiryConfig->getExpiryDayBefore();
        // Empty Value disables notification
        if (!$inDays) {
            return $this;
        }

        // join info about current balance and filter records by website
        $this->_joinReward();
        $this->addWebsiteFilter($websiteId);

        $field = $expiryConfig->getExpiryCalculation() == 'static' ? 'expired_at_static' : 'expired_at_dynamic';
        $expireAtLimit = (new \DateTime('now', new \DateTimeZone('UTC')))
            ->add(new \DateInterval('P' . $inDays . 'D'))
            ->format('Y-m-d H:i:s');

        $this->getSelect()->columns(
            ['total_expired' => new \Zend_Db_Expr('SUM(points_delta-points_used)')]
        )->where(
            'points_delta-points_used > 0'
        )->where(
            'is_expired=0'
        )->where(
            "{$field} IS NOT NULL" // expire_at - BEFORE_DAYS < NOW
        )->where(
            // eq. expire_at - BEFORE_DAYS < NOW
            "{$field} < ?",
            $expireAtLimit
        )->group(
            ['reward_table.customer_id', 'main_table.store_id']
        );

        if ($subscribedOnly) {
            $this->addCustomerInfo();
            $this->getSelect()->where('warning_notification.value=1');
        }

        $this->setFlag('expired_soon_points_loaded', true);

        return $this;
    }

    /**
     * Add filter for notification_sent field
     *
     * @param bool $flag
     * @return $this
     */
    public function addNotificationSentFlag($flag)
    {
        $this->addFieldToFilter('notification_sent', (bool)$flag ? 1 : 0);
        return $this;
    }

    /**
     * Return array of history ids records that will be expired.
     *
     * Required loadExpiredSoonPoints() call first, based on its select object
     *
     * @return array|bool
     */
    public function getExpiredSoonIds()
    {
        if (!$this->getFlag('expired_soon_points_loaded')) {
            return [];
        }

        $additionalWhere = [];
        foreach ($this as $item) {
            $where = [
                $this->getConnection()->quoteInto('reward_table.customer_id=?', $item->getCustomerId()),
                $this->getConnection()->quoteInto('main_table.store_id=?', $item->getStoreId()),
            ];
            $additionalWhere[] = '(' . implode(' AND ', $where) . ')';
        }
        if (count($additionalWhere) == 0) {
            return [];
        }
        // filter rows by customer and store, as result of grouped query
        $where = new \Zend_Db_Expr(implode(' OR ', $additionalWhere));

        $select = clone $this->getSelect();
        $select->reset(
            \Magento\Framework\DB\Select::COLUMNS
        )->columns(
            'history_id'
        )->reset(
            \Magento\Framework\DB\Select::GROUP
        )->reset(
            \Magento\Framework\DB\Select::LIMIT_COUNT
        )->reset(
            \Magento\Framework\DB\Select::LIMIT_OFFSET
        )->where(
            $where
        );

        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Order by primary key desc
     *
     * @return $this
     * @codeCoverageIgnore
     */
    public function setDefaultOrder()
    {
        $this->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);

        return $this->addOrder('created_at', 'DESC')->addOrder('history_id', 'DESC');
    }
}
