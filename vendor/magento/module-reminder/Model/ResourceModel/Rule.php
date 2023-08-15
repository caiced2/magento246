<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Reminder\Model\Rule as ModelRule;
use Magento\SalesRule\Model\Rule as SalesRule;

/**
 * Reminder Rule resource model
 */
class Rule extends \Magento\Rule\Model\ResourceModel\AbstractResource
{
    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website' => [
            'associations_table' => 'magento_reminder_rule_website',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'website_id',
        ],
    ];

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    private $dateTime;

    /**
     * Core resource helper
     *
     * @var \Magento\Framework\DB\Helper
     */
    protected $_resourceHelper;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\DB\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->_resourceHelper = $resourceHelper;
        $this->dateTime = $dateTime;
    }

    /**
     * Initialize main table and table id field
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_reminder_rule', 'rule_id');
    }

    /**
     * Add website ids to rule data after load
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $object->setData('website_ids', (array)$this->getWebsiteIds($object->getId()));

        parent::_afterLoad($object);
        return $this;
    }

    /**
     * Bind reminder rule to and website(s).  Save store templates data.
     *
     * @param AbstractModel $rule
     * @return $this
     */
    protected function _afterSave(AbstractModel $rule)
    {
        if ($rule->hasWebsiteIds()) {
            $websiteIds = $rule->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = explode(',', (string)$websiteIds);
            }
            $this->bindRuleToEntity($rule->getId(), $websiteIds, 'website');
        }

        if ($rule->hasData('store_templates')) {
            $this->_saveStoreData($rule);
        }

        parent::_afterSave($rule);
        return $this;
    }

    /**
     * Save store templates
     *
     * @param ModelRule $rule
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _saveStoreData($rule)
    {
        $connection = $this->getConnection();
        $templateTable = $this->getTable('magento_reminder_template');
        $labels = (array)$rule->getStoreLabels();
        $descriptions = (array)$rule->getStoreDescriptions();
        $templates = (array)$rule->getStoreTemplates();
        $ruleId = $rule->getId();

        $data = [];
        foreach ($templates as $storeId => $templateId) {
            if (!$templateId) {
                continue;
            }
            if (!is_numeric($templateId)) {
                $templateId = null;
            }
            $data[] = [
                'rule_id' => $ruleId,
                'store_id' => $storeId,
                'template_id' => $templateId,
                'label' => isset($labels[$storeId]) ? $labels[$storeId] : '',
                'description' => isset($descriptions[$storeId]) ? $descriptions[$storeId] : '',
            ];
        }

        $connection->beginTransaction();
        try {
            $connection->delete($templateTable, ['rule_id=?' => $ruleId]);
            if (!empty($data)) {
                $connection->insertMultiple($templateTable, $data);
            }
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
        $connection->commit();
        return $this;
    }

    /**
     * Get store templates data assigned to reminder rule
     *
     * @param int $ruleId
     * @return array
     */
    public function getStoreData($ruleId)
    {
        $templateTable = $this->getTable('magento_reminder_template');
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $templateTable,
            ['store_id', 'template_id', 'label', 'description']
        )->where(
            'rule_id = :rule_id'
        );
        return $connection->fetchAll($select, ['rule_id' => $ruleId]);
    }

    /**
     * Get store templates data (labels and descriptions) assigned to reminder rule.
     *
     * If labels and descriptions are not specified it will be replaced with default values.
     *
     * @param int $ruleId
     * @param int $storeId
     * @return array
     */
    public function getStoreTemplateData($ruleId, $storeId)
    {
        $templateTable = $this->getTable('magento_reminder_template');
        $ruleTable = $this->getTable('magento_reminder_rule');
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            ['t' => $templateTable],
            [
                'template_id',
                'label' => $connection->getCheckSql('t.label IS NOT NULL', 't.label', 'r.default_label'),
                'description' => $connection->getCheckSql(
                    't.description IS NOT NULL',
                    't.description',
                    'r.default_description'
                )
            ]
        )->join(
            ['r' => $ruleTable],
            'r.rule_id = t.rule_id',
            []
        );

        $select->where('t.rule_id = :rule_id');
        $select->where('t.store_id = :store_id');

        return $connection->fetchRow($select, ['rule_id' => $ruleId, 'store_id' => $storeId]);
    }

    /**
     * Deactivate already matched customers before new matching process
     *
     * @param int $ruleId
     * @return $this
     */
    public function deactivateMatchedCustomers($ruleId)
    {
        $this->getConnection()->update(
            $this->getTable('magento_reminder_rule_coupon'),
            ['is_active' => '0'],
            ['rule_id = ?' => $ruleId]
        );
        return $this;
    }

    /**
     * Try to associate reminder rule with matched customers.
     *
     * If customer was added earlier, update is_active column.
     *
     * @param ModelRule $rule
     * @param SalesRule $salesRule
     * @param int $websiteId
     * @param int $threshold
     * @return $this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function saveMatchedCustomers($rule, $salesRule, $websiteId, $threshold = null)
    {
        $rule->afterLoad();
        /** @var $select \Magento\Framework\DB\Select */
        $select = $rule->getConditions()->getConditionsSql(null, $websiteId);

        if (!$rule->getConditionSql()) {
            return $this;
        }

        if ($threshold) {
            $select->where('c.emails_failed IS NULL OR c.emails_failed < ? ', $threshold);
        }

        $ruleId = $rule->getId();
        $connection = $this->getConnection();
        $couponsTable = $this->getTable('magento_reminder_rule_coupon');
        $currentDate = $this->dateTime->formatDate(time());
        $dataToInsert = [];

        $stmt = $connection->query($select, ['rule_id' => $ruleId]);

        $connection->beginTransaction();
        try {
            $i = 0;
            while (true == ($row = $stmt->fetch())) {
                if (empty($row['coupon_id']) && $salesRule) {
                    $coupon = $salesRule->acquireCoupon();
                    $couponId = $coupon !== null ? $coupon->getId() : null;
                } else {
                    $couponId = $row['coupon_id'];
                }

                $dataToInsert[] = [
                    'rule_id' => $ruleId,
                    'coupon_id' => $couponId,
                    'customer_id' => $row['entity_id'],
                    'associated_at' => $currentDate,
                    'is_active' => '1',
                ];
                $i++;

                if ($i % 1000 == 0) {
                    $connection->insertOnDuplicate($couponsTable, $dataToInsert, ['is_active']);
                    $dataToInsert = [];
                }
            }
            if (!empty($dataToInsert)) {
                $connection->insertOnDuplicate($couponsTable, $dataToInsert, ['is_active']);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        $connection->commit();
        return $this;
    }

    /**
     * @param array $relatedCustomers
     * @param \Magento\Reminder\Model\Rule $rule
     * @param \Magento\SalesRule\Model\Rule $salesRule
     * @return $this
     * @throws \Exception
     */
    public function saveSatisfiedCustomers($relatedCustomers, $rule, $salesRule)
    {
        $connection = $this->getConnection();
        $couponsTable = $this->getTable('magento_reminder_rule_coupon');
        $currentDate = $this->dateTime->formatDate(time());
        $dataToInsert = [];

        $connection->beginTransaction();
        try {
            foreach ($relatedCustomers as $k => $customer) {
                if (empty($customer['coupon_id']) && $salesRule) {
                    $coupon = $salesRule->acquireCoupon();
                    $couponId = $coupon !== null ? $coupon->getId() : null;
                } else {
                    $couponId = $customer['coupon_id'];
                }

                $dataToInsert[] = [
                    'rule_id' => $rule->getId(),
                    'coupon_id' => $couponId,
                    'customer_id' => $customer['entity_id'],
                    'associated_at' => $currentDate,
                    'is_active' => '1',
                ];

                if ($k % 1000 == 0) {
                    $connection->insertOnDuplicate($couponsTable, $dataToInsert, ['is_active']);
                    $dataToInsert = [];
                }
            }
            if (!empty($dataToInsert)) {
                $connection->insertOnDuplicate($couponsTable, $dataToInsert, ['is_active']);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        $connection->commit();
        return $this;
    }

    /**
     * Retrieve list of customers for notification process.
     *
     * This process can be initialized by system cron or by admin for particular rule
     *
     * @param int|null $limit
     * @param int|null $ruleId
     * @return array
     */
    public function getCustomersForNotification($limit = null, $ruleId = null)
    {
        $couponTable = $this->getTable('magento_reminder_rule_coupon');
        $ruleTable = $this->getTable('magento_reminder_rule');
        $logTable = $this->getTable('magento_reminder_rule_log');
        $connection = $this->getConnection();
        $currentDate = $this->dateTime->formatDate(time());

        $select = $connection->select()->from(
            ['c' => $couponTable],
            ['customer_id', 'coupon_id', 'rule_id']
        )->join(
            ['r' => $ruleTable],
            'c.rule_id = r.rule_id AND r.is_active = 1',
            ['schedule' => 'schedule']
        )->joinLeft(
            ['l' => $logTable],
            'c.rule_id = l.rule_id AND c.customer_id = l.customer_id',
            []
        );

        if ($ruleId) {
            $select->where('c.rule_id = ?', $ruleId);
        }

        $select->where('c.is_active = 1');
        $select->group(['c.customer_id', 'c.rule_id']);
        $select->columns(['log_sent_at_max' => 'MAX(l.sent_at)', 'log_sent_at_min' => 'MIN(l.sent_at)']);

        $findInSetSql = $connection->prepareSqlCondition(
            'schedule',
            [
                'finset' => $this->_resourceHelper->getDateDiff(
                    'log_sent_at_min',
                    $connection->formatDate($currentDate)
                )
            ]
        );
        $select->having(
            'log_sent_at_max IS NULL OR (' . $findInSetSql . ' AND ' . $this->_resourceHelper->getDateDiff(
                'log_sent_at_max',
                $connection->formatDate($currentDate)
            ) . ' > 0)'
        );

        if ($limit) {
            $select->limit($limit);
        }
        return $connection->fetchAll($select);
    }

    /**
     * Add notification log row after letter was successfully sent.
     *
     * @param int $ruleId
     * @param int $customerId
     * @return $this
     */
    public function addNotificationLog($ruleId, $customerId)
    {
        $data = [
            'rule_id' => $ruleId,
            'customer_id' => $customerId,
            'sent_at' => $this->dateTime->formatDate(time()),
        ];

        $this->getConnection()->insert($this->getTable('magento_reminder_rule_log'), $data);

        return $this;
    }

    /**
     * Update failed email counter.
     *
     * @param int $ruleId
     * @param int $customerId
     * @return $this
     */
    public function updateFailedEmailsCounter($ruleId, $customerId)
    {
        $this->getConnection()->update(
            $this->getTable('magento_reminder_rule_coupon'),
            ['emails_failed' => new \Zend_Db_Expr('emails_failed + 1')],
            ['rule_id = ?' => $ruleId, 'customer_id = ?' => $customerId]
        );
        return $this;
    }

    /**
     * Retrieve count of reminder rules assigned to specified sales rule.
     *
     * @param int $salesRuleId
     * @return string
     */
    public function getAssignedRulesCount($salesRuleId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['r' => $this->getTable('magento_reminder_rule')],
            [new \Zend_Db_Expr('count(1)')]
        );
        $select->where('r.salesrule_id = :salesrule_id');

        return $connection->fetchOne($select, ['salesrule_id' => $salesRuleId]);
    }

    /**
     * Detaches sales rule from all Email Remainder Rules that uses it
     *
     * @param int $salesRuleId
     * @return $this
     */
    public function detachSalesRule($salesRuleId)
    {
        $this->getConnection()->update(
            $this->getTable('magento_reminder_rule'),
            ['salesrule_id' => new \Zend_Db_Expr('NULL')],
            ['salesrule_id = ?' => $salesRuleId]
        );

        return $this;
    }

    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     *
     * @param string $operator
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSqlOperator($operator)
    {
        switch ($operator) {
            case '==':
                return '=';
            case '!=':
                return '<>';
            case '{}':
                return 'LIKE';
            case '!{}':
                return 'NOT LIKE';
            case 'between':
                return 'BETWEEN %s AND %s';
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $operator;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown operator specified.'));
                break;
        }
    }

    /**
     * Create string for select "where" condition based on field name, comparison operator and vield value
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return string
     */
    public function createConditionSql($field, $operator, $value)
    {
        $sqlOperator = $this->getSqlOperator($operator);
        $connection = $this->getConnection();

        $condition = '';
        switch ($operator) {
            case '{}':
            case '!{}':
                if (is_array($value)) {
                    if (!empty($value)) {
                        $sqlOperator = $operator == '{}' ? 'IN' : 'NOT IN';
                        $condition = $connection->quoteInto($field . ' ' . $sqlOperator . ' (?)', $value);
                    }
                } else {
                    $condition = $connection->quoteInto($field . ' ' . $sqlOperator . ' ?', '%' . $value . '%');
                }
                break;
            case 'between':
                $condition = $field . ' ' . sprintf(
                    $sqlOperator,
                    $connection->quote($value['start']),
                    $connection->quote($value['end'])
                );
                break;
            default:
                $condition = $connection->quoteInto($field . ' ' . $sqlOperator . ' ?', $value);
                break;
        }

        return $condition;
    }

    /**
     * Get empty select object
     *
     * @return \Magento\Framework\DB\Select
     */
    public function createSelect()
    {
        return $this->getConnection()->select();
    }
}
