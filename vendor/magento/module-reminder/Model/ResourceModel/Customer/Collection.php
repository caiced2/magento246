<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Model\ResourceModel\Customer;

use Magento\Framework\Registry;

/**
 * Resource collection of customers matched by reminder rule
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Customer\Model\ResourceModel\Customer\Collection
{
    /**
     * Core registry
     *
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Magento\Framework\DataObject\Copy\Config $fieldsetConfig
     * @param Registry $coreRegistry
     * @param mixed $connection
     * @param string $modelName
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        Registry $coreRegistry,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        $modelName = self::CUSTOMER_MODEL_NAME
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $entitySnapshot,
            $fieldsetConfig,
            $connection,
            $modelName
        );
    }

    /**
     * Instantiate select to get matched customers
     *
     * @return $this
     */
    protected function _initSelect()
    {
        $rule = $this->_coreRegistry->registry('current_reminder_rule');
        $select = $this->getSelect();

        $customerTable = $this->getTable('customer_entity');
        $couponTable = $this->getTable('magento_reminder_rule_coupon');
        $logTable = $this->getTable('magento_reminder_rule_log');
        $salesRuleCouponTable = $this->getTable('salesrule_coupon');

        $select->from(['c' => $couponTable], ['associated_at', 'emails_failed', 'is_active']);
        $select->where('c.rule_id = ?', $rule->getId());

        $select->joinInner(['e' => $customerTable], 'e.entity_id = c.customer_id', ['entity_id', 'email']);

        $subSelect = $this->getConnection()->select();
        $subSelect->from(
            ['g' => $logTable],
            [
                'customer_id',
                'rule_id',
                'emails_sent' => new \Zend_Db_Expr('COUNT(log_id)'),
                'last_sent' => new \Zend_Db_Expr('MAX(sent_at)')
            ]
        );

        $subSelect->where('rule_id = ?', $rule->getId());
        $subSelect->group(['customer_id', 'rule_id']);

        $select->joinLeft(
            ['l' => $subSelect],
            'l.rule_id = c.rule_id AND l.customer_id = c.customer_id',
            ['l.emails_sent', 'l.last_sent']
        );

        $select->joinLeft(
            ['sc' => $salesRuleCouponTable],
            'sc.coupon_id = c.coupon_id',
            ['code', 'usage_limit', 'usage_per_customer']
        );

        $this->_joinFields['associated_at'] = ['table' => 'c', 'field' => 'associated_at'];
        $this->_joinFields['emails_failed'] = ['table' => 'c', 'field' => 'emails_failed'];
        $this->_joinFields['is_active'] = ['table' => 'c', 'field' => 'is_active'];
        $this->_joinFields['code'] = ['table' => 'sc', 'field' => 'code'];
        $this->_joinFields['usage_limit'] = ['table' => 'sc', 'field' => 'usage_limit'];
        $this->_joinFields['usage_per_customer'] = ['table' => 'sc', 'field' => 'usage_per_customer'];
        $this->_joinFields['emails_sent'] = ['table' => 'l', 'field' => 'emails_sent'];
        $this->_joinFields['last_sent'] = ['table' => 'l', 'field' => 'last_sent'];

        return $this;
    }
}
