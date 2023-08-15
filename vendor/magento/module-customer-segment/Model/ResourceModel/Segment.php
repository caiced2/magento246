<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\ResourceModel;

use Magento\Customer\Model\Config\Share;
use Magento\CustomerSegment\Model\ResourceModel\Customer\LinksMatcher;
use Magento\CustomerSegment\Model\Segment as SegmentModel;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime;
use Magento\Quote\Model\QueryResolver;
use Magento\Quote\Model\ResourceModel\Quote;
use Magento\Rule\Model\ResourceModel\AbstractResource;

/**
 * Customer segment resource model
 *
 * @api
 * @since 100.0.2
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Segment extends AbstractResource
{
    /**
     * @var Share
     */
    protected $_configShare;

    /**
     * @var Helper
     */
    protected $_resourceHelper;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var QueryResolver
     */
    protected $queryResolver;

    /**
     * @var Quote
     */
    protected $resourceQuote;

    /**
     * @var LinksMatcher
     */
    private $customerLinksMatcher;

    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'magento_customersegment_website',
            'rule_id_field' => 'segment_id',
            'entity_id_field' => 'website_id',
        ],
        'event' => [
            'associations_table' => 'magento_customersegment_event',
            'rule_id_field' => 'segment_id',
            'entity_id_field' => 'event',
        ],
    ];

    /**
     * Segment websites table name
     *
     * @var string
     */
    protected $_websiteTable;

    /**
     * @param Context $context
     * @param Helper $resourceHelper
     * @param Share $configShare
     * @param DateTime $dateTime
     * @param Quote $resourceQuote
     * @param QueryResolver $queryResolver
     * @param string $connectionName
     * @param LinksMatcher|null $customerLinksMatcher
     */
    public function __construct(
        Context $context,
        Helper $resourceHelper,
        Share $configShare,
        DateTime $dateTime,
        Quote $resourceQuote,
        QueryResolver $queryResolver,
        $connectionName = null,
        LinksMatcher $customerLinksMatcher = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_resourceHelper = $resourceHelper;
        $this->_configShare = $configShare;
        $this->dateTime = $dateTime;
        $this->resourceQuote = $resourceQuote;
        $this->queryResolver = $queryResolver;
        $this->customerLinksMatcher = $customerLinksMatcher
            ?: ObjectManager::getInstance()->create(LinksMatcher::class);
    }

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_customersegment_segment', 'segment_id');
        $this->_websiteTable = $this->getTable('magento_customersegment_website');
    }

    /**
     * Add website ids to rule data after load
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $object->setData('website_ids', (array)$this->getWebsiteIds($object->getId()));

        parent::_afterLoad($object);
        return $this;
    }

    /**
     * Match and save events.
     *
     * Save websites associations
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        $segmentId = $object->getId();

        $this->unbindRuleFromEntity($segmentId, [], 'event');
        if ($object->hasMatchedEvents()) {
            $matchedEvents = $object->getMatchedEvents();
            if (is_array($matchedEvents) && !empty($matchedEvents)) {
                $this->bindRuleToEntity($segmentId, $matchedEvents, 'event');
            }
        }

        if ($object->hasWebsiteIds()) {
            $websiteIds = $object->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindRuleToEntity($segmentId, $websiteIds, 'website');
        }

        parent::_afterSave($object);
        return $this;
    }

    /**
     * Delete association between customer and segment for specific segment
     *
     * @param SegmentModel $segment
     * @return $this
     */
    public function deleteSegmentCustomers($segment)
    {
        $this->getConnection()->delete(
            $this->getTable('magento_customersegment_customer'),
            ['segment_id=?' => $segment->getId()]
        );
        return $this;
    }

    /**
     * Save customer Ids matched by segment SQL select on specific website
     *
     * @param SegmentModel $segment
     * @param string $select
     * @return $this
     * @throws \Exception
     */
    public function saveCustomersFromSelect($segment, $select)
    {
        $customerTable = $this->getTable('magento_customersegment_customer');
        $connection = $this->getConnection();
        $segmentId = $segment->getId();
        $now = $this->dateTime->formatDate(time());

        $data = [];
        $count = 0;
        $stmt = $connection->query($select);
        $connection->beginTransaction();
        try {
            while ($row = $stmt->fetch()) {
                $data[] = [
                    'segment_id' => $segmentId,
                    'customer_id' => $row['entity_id'],
                    'website_id' => $row['website_id'],
                    'added_date' => $now,
                    'updated_date' => $now,
                ];
                $count++;
                if ($count % 1000 == 0) {
                    $connection->insertMultiple($customerTable, $data);
                    $data = [];
                }
            }
            if (!empty($data)) {
                $connection->insertMultiple($customerTable, $data);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $connection->commit();

        return $this;
    }

    /**
     * Count customers in specified segment
     *
     * @param int $segmentId
     * @return int
     */
    public function getSegmentCustomersQty($segmentId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getTable('magento_customersegment_customer'),
            ['COUNT(DISTINCT customer_id)']
        )->where(
            'segment_id = ?',
            (int)$segmentId
        );

        return (int)$connection->fetchOne($select);
    }

    /**
     * Aggregate customer/segments relations by matched segment conditions
     *
     * @param SegmentModel $segment
     * @return $this
     * @throws \Exception
     */
    public function aggregateMatchedCustomers($segment)
    {
        $this->customerLinksMatcher->matchCustomerLinks($segment);

        return $this;
    }

    /**
     * Process conditions
     *
     * @param SegmentModel $segment
     * @return $this
     * @throws \Exception
     * @deprecated This method is not intended for usage in child classes
     * @see \Magento\CustomerSegment\Model\ResourceModel\Customer\LinksMatcher::matchCustomerLinks
     */
    protected function processConditions($segment)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        $this->customerLinksMatcher->matchLinks($segment);

        return $this;
    }

    /**
     * Save matched customer
     *
     * @param array $relatedCustomers
     * @param SegmentModel $segment
     * @return $this
     * @throws \Exception
     * @deprecated This method is not intended for usage in child classes
     * @see \Magento\CustomerSegment\Model\ResourceModel\Customer\LinksMatcher::matchCustomerLinks
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function saveMatchedCustomer($relatedCustomers, $segment)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        $this->customerLinksMatcher->matchLinks($segment);

        return $this;
    }

    /**
     * Get select query result
     *
     * @param Select|string $sql
     * @param array $bindParams array of bind variables
     * @return int
     */
    public function runConditionSql($sql, $bindParams)
    {
        return $this->getConnection()->fetchOne($sql, $bindParams);
    }

    /**
     * Get empty select object
     *
     * @return Select
     */
    public function createSelect()
    {
        return $this->getConnection()->select();
    }

    /**
     * Quote parameters into condition string
     *
     * @param string $string
     * @param string|array $param
     * @return string
     */
    public function quoteInto($string, $param)
    {
        return $this->getConnection()->quoteInto($string, $param);
    }

    /**
     * Get comparison condition for rule condition.
     *
     * Operator which will be used in SQL query depending of database we using
     *
     * @param string $operator
     * @return string
     */
    public function getSqlOperator($operator)
    {
        return $this->_resourceHelper->getSqlOperator($operator);
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and field value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createConditionSql($field, $operator, $value)
    {
        if (!is_array($value)) {
            $prepareValues = explode(',', $value);
            if (count($prepareValues) <= 1) {
                $value = $prepareValues[0];
            } else {
                $value = [];
                foreach ($prepareValues as $val) {
                    $value[] = trim($val);
                }
            }
        }

        /*
         * substitute "equal" operator with "is one of" if compared value is not single
         */
        if ((is_array($value) || $value instanceof \Countable)
            && count($value) != 1
            && in_array($operator, ['==', '!='])
        ) {
            $operator = $operator == '==' ? '()' : '!()';
        }
        $sqlOperator = $this->getSqlOperator($operator);
        $condition = '';

        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $condition = [];
                        foreach ($value as $val) {
                            $condition[] = $this->getConnection()->quoteInto(
                                $field . ' ' . $sqlOperator . ' ?',
                                '%' . $val . '%'
                            );
                        }
                        $condition = implode(' AND ', $condition);
                    }
                } else {
                    $condition = $this->getConnection()->quoteInto(
                        $field . ' ' . $sqlOperator . ' ?',
                        '%' . $value . '%'
                    );
                }
                break;
            case '()':
            case '!()':
                if (is_array($value) && !empty($value)) {
                    $condition = $this->getConnection()->quoteInto($field . ' ' . $sqlOperator . ' (?)', $value);
                }
                break;
            case '[]':
            case '![]':
                if (is_array($value) && !empty($value)) {
                    $conditions = [];
                    foreach ($value as $v) {
                        $conditions[] = $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($v)]
                        );
                    }
                    $condition = sprintf('(%s)%s', join(' OR ', $conditions), $operator == '[]' ? '>0' : '=0');
                } else {
                    if ($operator == '[]') {
                        $condition = $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($value)]
                        );
                    } else {
                        $condition = 'NOT (' . $this->getConnection()->prepareSqlCondition(
                            $field,
                            ['finset' => $this->getConnection()->quote($value)]
                        ) . ')';
                    }
                }
                break;
            case 'finset':
            case '!finset':
                $condition = $this->prepareFindInSetCondition($field, $operator, $value);
                break;
            case 'between':
                $condition = $field . ' ' . sprintf(
                    $sqlOperator,
                    $this->getConnection()->quote($value['start']),
                    $this->getConnection()->quote($value['end'])
                );
                break;
            default:
                $condition = $this->getConnection()->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }
        return $condition;
    }

    /**
     * Prepare SQL condition for 'finset' pseudo-operator
     *
     * 'finset' pseudo-operator is required to correctly processing multiple select's values
     * in case of '==' or '!=' operators selected for comparison operation.
     *
     * @param string $field
     * @param string $operator
     * @param array|string $value
     * @return string
     */
    private function prepareFindInSetCondition($field, $operator, $value)
    {
        $condition = '';
        if (is_array($value)) {
            $conditions = [];
            foreach ($value as $v) {
                $sqlCondition = $this->getConnection()->prepareSqlCondition(
                    $field,
                    ['finset' => $this->getConnection()->quote($v)]
                );
                $sqlCondition .= ($operator == 'finset' ? '>0' : '=0');
                $conditions[] = $sqlCondition;
            }
            if ($operator == 'finset') {
                $condition = join(' AND ', $conditions)
                    . ' AND '
                    . strlen(implode(',', $value)) . '=' . $this->getConnection()->getLengthSql($field);
            } else {
                $condition = join(' OR ', $conditions)
                    . ' OR '
                    . strlen(implode(',', $value)) . '<>' . $this->getConnection()->getLengthSql($field);
            }
        }
        return $condition;
    }

    /**
     * Save all website Ids associated to specified segment
     *
     * @param AbstractModel|SegmentModel $segment
     * @return $this
     * after 1.11.2.0 use $this->bindRuleToEntity() instead
     */
    protected function _saveWebsiteIds($segment)
    {
        if ($segment->hasWebsiteIds()) {
            $websiteIds = $segment->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindRuleToEntity($segment->getId(), $websiteIds, 'website');
        }

        return $this;
    }

    /**
     * Get Active Segments By Ids
     *
     * @param int[] $segmentIds
     * @return int[]
     */
    public function getActiveSegmentsByIds($segmentIds)
    {
        $activeSegmentsIds = [];
        if (count($segmentIds)) {
            $connection = $this->getConnection();
            $select = $connection->select()->from(
                $this->getMainTable(),
                ['segment_id']
            )->where(
                'segment_id IN (?)',
                $segmentIds
            )->where(
                'is_active = 1'
            );

            $segmentsList = $connection->fetchAll($select);
            foreach ($segmentsList as $segment) {
                $activeSegmentsIds[] = $segment['segment_id'];
            }
        }
        return $activeSegmentsIds;
    }
}
