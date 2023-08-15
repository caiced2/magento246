<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Customer;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\AbstractCondition;

/**
 * Customer newsletter subscription
 */
class Newsletter extends AbstractCondition
{
    /**
     * @var string
     */
    protected $_inputType = 'select';

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = []
    ) {
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Customer\Newsletter::class);
        $this->setValue(1);
    }

    /**
     * Set data with filtering
     *
     * @param array|string $key
     * @param mixed $value
     * @return $this
     */
    public function setData($key, $value = null)
    {
        //filter key "value"
        if (is_array($key) && isset($key['value']) && $key['value'] !== null) {
            $key['value'] = (int)$key['value'];
        } elseif ($key == 'value' && $value !== null) {
            $value = (int)$value;
        }

        return parent::setData($key, $value);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched
     *
     * @return string[]
     */
    public function getMatchedEvents()
    {
        return ['customer_save_commit_after', 'newsletter_subscriber_save_commit_after'];
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        return [['value' => $this->getType(), 'label' => __('Newsletter Subscription')]];
    }

    /**
     * Get HTML of condition string
     *
     * @return string
     */
    public function asHtml()
    {
        $element = $this->getValueElementHtml();
        return $this->getTypeElementHtml() . __(
            'Customer is %1 to newsletter.',
            $element
        ) . $this->getRemoveLinkHtml();
    }

    /**
     * Get element type for value select
     *
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * Init list of available values
     *
     * @return $this
     */
    public function loadValueOptions()
    {
        $this->setValueOption(['1' => __('subscribed'), '0' => __('not subscribed')]);
        return $this;
    }

    /**
     * Get condition query for customer balance
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        $table = $this->getResource()->getTable('newsletter_subscriber');
        $value = (int)$this->getValue();
        $select = $this->getResource()->createSelect();
        if ($isFiltered) {
            $select->from(
                ['main' => $table],
                [new \Zend_Db_Expr($value)]
            )->where(
                $this->_createCustomerFilter($customer, 'main.customer_id')
            );
            $select->limit(1);
            $select->where(
                'main.subscriber_status = ?',
                \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            );
            $this->_limitByStoreWebsite($select, $website, 'main.store_id');
            if (!$value) {
                $select = $this->getResource()->getConnection()->getIfNullSql($select, 1);
            }
        } elseif ($value) {
            $select->from(
                ['main' => $table],
                ['customer_id']
            );
            $select->where(
                'main.subscriber_status = ?',
                \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            );

            $this->_limitByStoreWebsite($select, $website, 'main.store_id');
        } else {
            $subSelect = $this->getResource()->createSelect();
            $subSelect->from(
                ['main' => $table],
                [new \Zend_Db_Expr($value)]
            )->where(
                $this->_createCustomerFilter($customer, 'main.customer_id')
            );
            $subSelect->limit(1);
            $subSelect->where(
                'main.subscriber_status = ?',
                \Magento\Newsletter\Model\Subscriber::STATUS_SUBSCRIBED
            );

            $this->_limitByStoreWebsite($subSelect, $website, 'main.store_id');
            if (!$value) {
                $subSelect = $this->getResource()->getConnection()->getIfNullSql($subSelect, 1);
            }
            $customerTable = ['root' => $this->getResource()->getTable('customer_entity')];
            $select->from($customerTable, ['entity_id']);
            $select->where('root.website_id=?', $website);
            $select->where(implode('AND', [$subSelect]));
        }
        return $select;
    }

    /**
     * @param int $customer
     * @param int $websiteId
     * @param array $params
     * @return bool
     */
    public function isSatisfiedBy($customer, $websiteId, $params)
    {
        if (!$customer) {
            return false;
        }
        $customerSelect = $this->getResource()->createSelect();
        $table = $this->getResource()->getTable('customer_entity');
        $customerSelect->from(['root' => $table], ['entity_id']);
        $select = $this->getConditionsSql($customer, $websiteId);
        $customerSelect->where($select);
        $matchedParams = $this->matchParameters($select, $params);
        $result = $this->getResource()->getConnection()->fetchOne($customerSelect, $matchedParams);
        return $result > 0;
    }
}
