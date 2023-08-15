<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\Segment\Condition\Sales;

use InvalidArgumentException;
use Magento\CustomerSegment\Model\ConditionFactory;
use Magento\CustomerSegment\Model\ResourceModel\Segment;
use Magento\Framework\App\ObjectManager;
use Magento\Rule\Model\Condition\Context;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Store\Api\StoreWebsiteRelationInterface;
use Zend_Db_Expr;

/**
 * Order numbers condition
 */
class Ordersnumber extends Combine
{
    /**
     * @var StoreWebsiteRelationInterface
     */
    private $storeManager;

    /**
     * @param Context $context
     * @param ConditionFactory $conditionFactory
     * @param Segment $resourceSegment
     * @param OrderResource $orderResource
     * @param array $data
     * @param StoreWebsiteRelationInterface|null $storeManager
     */
    public function __construct(
        Context $context,
        ConditionFactory $conditionFactory,
        Segment $resourceSegment,
        OrderResource $orderResource,
        array $data = [],
        ?StoreWebsiteRelationInterface $storeManager = null
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $orderResource, $data);
        $this->storeManager = $storeManager
            ?? ObjectManager::getInstance()->get(StoreWebsiteRelationInterface::class);
    }

    /**
     * Name of condition for displaying as html
     *
     * @var string
     */
    protected $frontConditionName = 'Number of Orders';

    /**
     * @inheritDoc
     */
    protected function getConditionSql($operator, $value)
    {
        $condition = $this->getResource()
            ->getConnection()
            ->getCheckSql("COUNT(*) {$operator} {$value}", 1, 0);
        return new Zend_Db_Expr($condition);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        if ($this->includeCustomersWithZeroOrders()) {
            $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
            $required = $this->_getRequiredValidation();
            $conditions = $this->processCombineSubFilters($website, $required, []);
            $operator = $operator = $this->getResource()->getSqlOperator($this->getOperator());
            $value = $this->getResource()->getConnection()->quote((double) $this->getValue());
            $select = $this->getResource()
                ->createSelect()
                ->from(
                    ['customer_entity' => $this->getResource()->getTable('customer_entity')],
                    []
                );
            $conditionSelect = $this->getResource()
                ->createSelect()
                ->from(
                    ['sales_order' => $this->getResource()->getTable('sales_order')],
                    [$this->getConditionSql($operator, $value)]
                )
                ->where('sales_order.customer_id = customer_entity.entity_id');

            $this->_limitByStoreWebsite($conditionSelect, $website, 'sales_order.store_id');

            if (!empty($conditions)) {
                $conditionSelect->where(implode($aggregator, $conditions));
            }

            if ($isFiltered) {
                $select->columns([$conditionSelect]);
                $select->where($this->_createCustomerFilter($customer, 'customer_entity.entity_id'));
            } else {
                $select->columns(['customer_entity.entity_id']);
                $select->having($conditionSelect);
            }
            return $select;
        }

        return parent::_prepareConditionsSql($customer, $website, $isFiltered);
    }

    /**
     * @inheritdoc
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        if ($this->includeCustomersWithZeroOrders()) {
            return $this->_prepareConditionsSql($customer, $website, $isFiltered);
        }

        return parent::getConditionsSql($customer, $website, $isFiltered);
    }

    /**
     * Checks if customers with zero orders match the condition
     *
     * Returns true if zero satisfies the condition. For instance:
     * - Total Number of Orders is equal or less than 2: should include customers with 0 or 1 or 2 orders
     * - Total Number of Orders is less than 2: should include customers with 0 or 1 order
     *
     * @return bool
     */
    private function includeCustomersWithZeroOrders(): bool
    {
        return $this->check(0);
    }

    /**
     * Checks if provided value satisfies the condition
     *
     * @param int $value
     * @return bool
     */
    private function check(int $value): bool
    {
        $operand = (int) $this->getValue();
        switch ($this->getOperator()) {
            case '==':
                return $value == $operand;
            case '!=':
                return $value != $operand;
            case '>=':
                return $value >= $operand;
            case '<=':
                return $value <= $operand;
            case '>':
                return $value > $operand;
            case '<':
                return $value < $operand;
            default:
                return false;
        }
    }

    /**
     * Get the opposite operator of current operator
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function getOppositeOperator(): string
    {
        switch ($this->getOperator()) {
            case '==':
                $operator = '!=';
                break;
            case '!=':
                $operator = '=';
                break;
            case '>=':
                $operator = '<';
                break;
            case '<=':
                $operator = '>';
                break;
            case '>':
                $operator = '<=';
                break;
            case '<':
                $operator = '>=';
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid operator "%s". Valid operators are %s',
                        $this->getOperator(),
                        implode(', ', $this->getDefaultOperatorInputByType()[$this->getInputType()])
                    )
                );
        }
        return $operator;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        $required = $this->_getRequiredValidation();
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $storeIds = $this->storeManager->getStoreByWebsiteId($websiteId);
        $connection = $this->orderResource->getConnection();
        $salesOrderTableName = $this->orderResource->getTable('sales_order');
        $select = $connection->select();
        $select->from(['sales_order' => $salesOrderTableName], [new Zend_Db_Expr('COUNT(*)')])
            ->where('sales_order.customer_id = ?', (int) $customer)
            ->where('sales_order.store_id IN (?)', array_map('intval', $storeIds));

        $conditions = $this->processCombineSubFilters($websiteId, $required, []);
        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }
        $count = $this->orderResource->getConnection()->fetchOne($select, $this->matchParameters($select, $params));
        return $this->check($count);
    }

    /**
     * @inheritdoc
     */
    public function getSatisfiedIds($websiteId)
    {
        $required = $this->_getRequiredValidation();
        $aggregator = $this->getAggregator() == 'all' ? ' AND ' : ' OR ';
        $storeIds = $this->storeManager->getStoreByWebsiteId($websiteId);
        $connection = $this->orderResource->getConnection();
        $salesOrderTableName = $this->orderResource->getTable('sales_order');
        $select = $connection->select();
        $value = (int) $this->getValue();
        $invert = $this->includeCustomersWithZeroOrders();
        $operator = $this->getResource()->getSqlOperator($invert ? $this->getOppositeOperator() : $this->getOperator());
        $select->from(['sales_order' => $salesOrderTableName], ['sales_order.customer_id'])
            ->where('sales_order.customer_id IS NOT NULL')
            ->where('sales_order.store_id IN (?)', array_map('intval', $storeIds))
            ->group(['sales_order.customer_id'])
            ->having(new Zend_Db_Expr("COUNT(*) {$operator} ?"), $value);

        $conditions = $this->processCombineSubFilters($websiteId, $required, []);
        if (!empty($conditions)) {
            $select->where(implode($aggregator, $conditions));
        }

        $result = $this->orderResource->getConnection()->fetchCol($select);
        if ($invert) {
            $result = $this->getCustomerIds($websiteId, $result);
        }

        return $result;
    }
}
