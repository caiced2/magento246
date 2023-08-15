<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\ObjectManager;

/**
 * Customer segment model
 *
 * @method string getName()
 * @method \Magento\CustomerSegment\Model\Segment setName(string $value)
 * @method string getDescription()
 * @method \Magento\CustomerSegment\Model\Segment setDescription(string $value)
 * @method int getIsActive()
 * @method \Magento\CustomerSegment\Model\Segment setIsActive(int $value)
 * @method string getConditionsSerialized()
 * @method \Magento\CustomerSegment\Model\Segment setConditionsSerialized(string $value)
 * @method int getProcessingFrequency()
 * @method \Magento\CustomerSegment\Model\Segment setProcessingFrequency(int $value)
 * @method string getConditionSql()
 * @method \Magento\CustomerSegment\Model\Segment setConditionSql(string $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Segment extends \Magento\Rule\Model\AbstractModel
{
    /**
     * Customer segment view modes
     */
    public const VIEW_MODE_UNION_CODE = 'union';

    public const VIEW_MODE_INTERSECT_CODE = 'intersect';

    /**
     * Possible states of customer segment
     */
    public const APPLY_TO_VISITORS = 2;

    public const APPLY_TO_REGISTERED = 1;

    public const APPLY_TO_VISITORS_AND_REGISTERED = 0;

    /**
     * @var \Magento\Rule\Model\Action\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Customer\Model\Visitor
     */
    protected $_visitor;

    /**
     * @var \Magento\Customer\Model\VisitorFactory
     */
    protected $_visitorFactory;

    /**
     * @var \Magento\CustomerSegment\Model\ConditionFactory
     */
    protected $_conditionFactory;

    /**
     * Store list manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\CustomerSegment\Model\Publisher
     */
    private $publisher;

    /**
     * Segment constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Rule\Model\Action\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Model\Visitor $visitor
     * @param \Magento\Customer\Model\VisitorFactory $visitorFactory
     * @param ConditionFactory $conditionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param \Magento\CustomerSegment\Model\SegmentMatchPublisher|null $publisher
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Rule\Model\Action\CollectionFactory $collectionFactory,
        \Magento\Customer\Model\Visitor $visitor,
        \Magento\Customer\Model\VisitorFactory $visitorFactory,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\CustomerSegment\Model\SegmentMatchPublisher $publisher = null
    ) {
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_visitor = $visitor;
        $this->_visitorFactory = $visitorFactory;
        $this->_conditionFactory = $conditionFactory;
        $this->publisher = $publisher
            ?? ObjectManager::getInstance()->get(\Magento\CustomerSegment\Model\SegmentMatchPublisher::class);
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
     * Set resource model
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);
    }

    /**
     * Set aggregated conditions SQL.
     *
     * Collect and save list of events which are applicable to segment.
     *
     * @return \Magento\CustomerSegment\Model\Segment
     */
    public function beforeSave()
    {
        if (!$this->getData('processing_frequency')) {
            $this->setData('processing_frequency', '1');
        }

        if (!$this->isObjectNew()) {
            // Keep 'apply_to' property without changes for existing customer segments
            $this->setData('apply_to', $this->getOrigData('apply_to'));
        }

        $events = [];
        if ($this->getIsActive()) {
            $events = $this->collectMatchedEvents();
        }
        $customer = new \Zend_Db_Expr(':customer_id');
        $website = new \Zend_Db_Expr(':website_id');
        $this->setConditionSql($this->getConditions()->getConditionsSql($customer, $website));
        $this->setMatchedEvents(array_unique($events));

        parent::beforeSave();
        return $this;
    }

    /**
     * Getter for rule combine conditions instance
     *
     * @return \Magento\CustomerSegment\Model\Segment\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->_conditionFactory->create('Combine\Root');
    }

    /**
     * Getter for rule actions collection instance
     *
     * @return \Magento\Rule\Model\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->_collectionFactory->create();
    }

    /**
     * Collect all matched event names for current segment
     *
     * @param null|\Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine $conditionsCombine
     *
     * @return array
     */
    public function collectMatchedEvents($conditionsCombine = null)
    {
        $events = [];
        if ($conditionsCombine === null) {
            $conditionsCombine = $this->getConditions();
        }
        $matchedEvents = $conditionsCombine->getMatchedEvents();
        if (!empty($matchedEvents)) {
            $events = array_merge($events, $matchedEvents);
        }
        $children = $conditionsCombine->getConditions();
        if ($children) {
            if (!is_array($children)) {
                $children = [$children];
            }
            foreach ($children as $child) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $events = array_merge($events, $this->collectMatchedEvents($child));
            }
        }

        if ($this->getApplyTo() != self::APPLY_TO_REGISTERED) {
            $events = array_merge($events, ['visitor_init']);
        }

        $events = array_unique($events);

        return $events;
    }

    /**
     * Get list of all models which are used in segment conditions
     *
     * @param  null|\Magento\Rule\Model\Condition\Combine $conditions
     *
     * @return array
     */
    public function getConditionModels($conditions = null)
    {
        $models = [];

        if ($conditions === null) {
            $conditions = $this->getConditions();
        }

        $models[] = $conditions->getType();
        $childConditions = $conditions->getConditions();
        if ($childConditions) {
            if (is_array($childConditions)) {
                foreach ($childConditions as $child) {
                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                    $models = array_merge($models, $this->getConditionModels($child));
                }
            } else {
                $models = array_merge($models, $this->getConditionModels($childConditions));
            }
        }

        return $models;
    }

    /**
     * Validate customer by segment conditions for current website
     *
     * @param \Magento\Framework\DataObject $object
     *
     * @return bool
     */
    public function validate(\Magento\Framework\DataObject $object)
    {
        $website = $this->_storeManager->getWebsite();
        if ($object instanceof \Magento\Customer\Model\Customer) {
            if (!$object->getId()) {
                $this->setVisitorId($this->_visitor->getId());
            }
            return $this->validateCustomer($object, $website);
        }
        return false;
    }

    /**
     * Check if customer is matched by segment
     *
     * @param int|\Magento\Customer\Model\Customer|\Magento\Framework\DataObject $customer
     * @param null|\Magento\Store\Model\Website|bool|int|string $website
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function validateCustomer($customer, $website)
    {
        $websiteId = $this->_storeManager->getWebsite($website)->getId();
        /**
         * Use prepared in beforeSave sql
         */
        $sql = $this->getConditionSql();
        if (!$sql) {
            return false;
        }
        if ($customer instanceof \Magento\Customer\Model\Customer) {
            $customerId = $customer->getId();
        } else {
            $customerId = $customer;
        }

        $params = [];
        if (strpos($sql, ':customer_id') !== false) {
            $params['customer_id'] = $customerId;
        }
        if (strpos($sql, ':website_id') !== false) {
            $params['website_id'] = $websiteId;
        }
        if (strpos($sql, ':quote_id') !== false) {
            if (!$customerId) {
                $params['quote_id'] = $this->getQuoteId();
            } else {
                $params['quote_id'] = 0;
            }
        }
        if (strpos($sql, ':visitor_id') !== false) {
            if (!$customerId) {
                $params['visitor_id'] = $this->getVisitorId();
            } else {
                $params['visitor_id'] = 0;
            }
        }

        return $this->getConditions()->isSatisfiedBy($customerId, $websiteId, $params);
    }

    /**
     * Match all customers by segment conditions and fill customer/segments relations table
     *
     * @return \Magento\CustomerSegment\Model\Segment
     */
    public function matchCustomers()
    {
        $this->matchCustomersQueue();
        return $this;
    }

    /**
     * Publish matched customers to queue
     *
     * @return bool
     * @throws LocalizedException
     */
    private function matchCustomersQueue(): bool
    {
        $result = $this->publisher->execute($this);
        if (!$result) {
            throw new LocalizedException(
                __('Something went wrong while processing the request.')
            );
        }

        return $result;
    }
}
