<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition\Customer;

use Magento\Customer\Model\Customer;
use Magento\CustomerSegment\Model\Condition\AbstractCondition;
use Magento\Framework\App\ObjectManager;
use Magento\Customer\Model\Config\Share;

/**
 * Customer attributes condition
 */
class Attributes extends AbstractCondition
{
    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $_eavConfig;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    protected $_resourceCustomer;

    /**
     * @var Share|null
     */
    private $configShare;

    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param array $data
     * @param Share|null $configShare
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        \Magento\Eav\Model\Config $eavConfig,
        array $data = [],
        Share $configShare = null
    ) {
        $this->_resourceCustomer = $resourceCustomer;
        $this->_eavConfig = $eavConfig;
        $this->configShare =  $configShare ?? ObjectManager::getInstance()->get(Share::class);
        parent::__construct($context, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Customer\Attributes::class);
        $this->setValue(null);
    }

    /**
     * Get array of event names where segment with such conditions combine can be matched.
     *
     * @return string[]
     */
    public function getMatchedEvents(): array
    {
        $events = ['customer_save_commit_after'];
        if ($this->_isCurrentAttributeDefaultAddress()) {
            $events = array_merge(
                $events,
                ['customer_address_save_commit_after', 'customer_address_delete_commit_after']
            );
        }

        return $events;
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $attributes = $this->loadAttributeOptions()->getAttributeOption();
        $conditions = [];
        foreach ($attributes as $code => $label) {
            $conditions[] = ['value' => $this->getType() . '|' . $code, 'label' => $label];
        }

        return $conditions;
    }

    /**
     * Retrieve attribute object
     *
     * @return \Magento\Eav\Model\Entity\Attribute
     */
    public function getAttributeObject()
    {
        return $this->_eavConfig->getAttribute('customer', $this->getAttribute());
    }

    /**
     * Load condition options for castomer attributes
     *
     * @return $this
     */
    public function loadAttributeOptions()
    {
        $productAttributes = $this->_resourceCustomer->loadAllAttributes()->getAttributesByCode();

        $attributes = [];

        foreach ($productAttributes as $attribute) {
            $label = $attribute->getFrontendLabel();
            if (!$label) {
                continue;
            }
            // skip "binary" attributes
            if (in_array($attribute->getFrontendInput(), ['file', 'image'])) {
                continue;
            }
            if ($attribute->getIsUsedForCustomerSegment()) {
                $attributes[$attribute->getAttributeCode()] = $label;
            }
        }
        asort($attributes);
        $this->setAttributeOption($attributes);
        return $this;
    }

    /**
     * Retrieve select option values
     *
     * @return array
     */
    public function getValueSelectOptions()
    {
        if (!$this->getData('value_select_options') && is_object($this->getAttributeObject())) {
            if ($this->getAttributeObject()->usesSource()) {
                if ($this->getAttributeObject()->getFrontendInput() == 'multiselect') {
                    $addEmptyOption = false;
                } else {
                    $addEmptyOption = true;
                }
                $optionsArr = $this->getAttributeObject()->getSource()->getAllOptions($addEmptyOption);
                $this->setData('value_select_options', $optionsArr);
            }

            if ($this->_isCurrentAttributeDefaultAddress()) {
                $optionsArr = $this->_getOptionsForAttributeDefaultAddress();
                $this->setData('value_select_options', $optionsArr);
            }
        }

        return $this->getData('value_select_options');
    }

    /**
     * Get input type for attribute operators.
     *
     * @return string
     */
    public function getInputType()
    {
        if ($this->_isCurrentAttributeDefaultAddress()) {
            return 'select';
        }
        if (!is_object($this->getAttributeObject())) {
            return 'string';
        }
        $input = $this->getAttributeObject()->getFrontendInput();
        switch ($input) {
            case 'boolean':
                return 'select';
            case 'select':
            case 'multiselect':
            case 'date':
                return $input;
            default:
                return 'string';
        }
    }

    /**
     * Get attribute value input element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        if ($this->_isCurrentAttributeDefaultAddress()) {
            return 'select';
        }
        if (!is_object($this->getAttributeObject())) {
            return 'text';
        }
        $input = $this->getAttributeObject()->getFrontendInput();
        switch ($input) {
            case 'boolean':
                return 'select';
            case 'select':
            case 'multiselect':
            case 'date':
                return $input;
            default:
                return 'text';
        }
    }

    /**
     * Check if attribute value should be explicit
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getExplicitApply()
    {
        if (is_object($this->getAttributeObject())) {
            switch ($this->getAttributeObject()->getFrontendInput()) {
                case 'date':
                    return true;
            }
        }
        return false;
    }

    /**
     * Retrieve attribute element
     *
     * @return \Magento\Framework\Data\Form\Element\AbstractElement
     */
    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);
        return $element;
    }

    /**
     * Get attribute operator html.
     *
     * @return string
     */
    public function getOperatorElementHtml(): string
    {
        return $this->_isCurrentAttributeDefaultAddress() ? '' : parent::getOperatorElementHtml();
    }

    /**
     * Check if current condition attribute is default billing or shipping address
     *
     * @return bool
     */
    protected function _isCurrentAttributeDefaultAddress()
    {
        $code = $this->getAttributeObject()->getAttributeCode();
        return $code == 'default_billing' || $code == 'default_shipping';
    }

    /**
     * Get options for customer default address attributes value select
     *
     * @return array
     */
    protected function _getOptionsForAttributeDefaultAddress()
    {
        return [
            ['value' => 'is_exists', 'label' => __('exists')],
            ['value' => 'is_not_exists', 'label' => __('does not exist')]
        ];
    }

    /**
     * Customer attributes are standalone conditions, hence they must be self-sufficient
     *
     * @return \Magento\Framework\Phrase
     */
    public function asHtml()
    {
        return __('Customer %1', parent::asHtml());
    }

    /**
     * Return values of start and end datetime for date if operator is equal
     *
     * @return array
     * @deprecated 101.0.0 Will be removed in major release.
     */
    public function getDateValue()
    {
        if ($this->getOperator() == '==') {
            $dateObj = (new \DateTime($this->getValue()))->setTime(0, 0, 0);
            $value = [
                'start' => $dateObj->format('Y-m-d H:i:s'),
                'end' => $dateObj->modify('+1 day')->format('Y-m-d H:i:s'),
            ];
            return $value;
        }
        return $this->getValue();
    }

    /**
     * Return date operator if original operator is equal
     *
     * @return string
     * @deprecated 101.0.0 Will be removed in major release.
     */
    public function getDateOperator()
    {
        if ($this->getOperator() == '==') {
            return 'between';
        }
        return $this->getOperator();
    }

    /**
     * Create SQL condition select for customer attribute
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     */
    public function getConditionsSql($customer, $website, $isFiltered = true)
    {
        if ($this->isVisitor($customer, $isFiltered)) {
            return $this->getSqlForReturnZero();
        }
        return $this->getCustomerConditionalSql($customer, $website, $isFiltered);
    }

    /**
     * Create SQL condition select for customer attribute
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getCustomerConditionalSql($customer, $website, $isFiltered)
    {
        $attribute = $this->getAttributeObject();
        $table = $attribute->getBackendTable();
        $select = $this->getResource()->createSelect();

        if ($isFiltered) {
            $select->from(['main' => $table], [new \Zend_Db_Expr(1)]);
            $select->where($this->_createCustomerFilter($customer, 'main.entity_id'));
            $select->limit(1);
        } else {
            $select->from(['main' => $table], ['entity_id']);
        }

        if (!in_array($attribute->getAttributeCode(), ['default_billing', 'default_shipping'])) {
            if ($attribute->isStatic()) {
                $field = "main.{$attribute->getAttributeCode()}";
            } else {
                $select->where('main.attribute_id = ?', $attribute->getId());
                $field = 'main.value';
            }
            $field = $select->getConnection()->quoteColumnAs($field, null);

            $condition = $this->getResource()->createConditionSql(
                $field,
                $this->getMappedOperator($attribute),
                $this->getMappedValue($attribute)
            );
            $select->where($condition);
        } else {
            if (!$isFiltered) {
                return $this->getCustomerSelect($website);
            }
            if ($this->getValue() == 'is_exists') {
                $ifCondition = 'COUNT(*) != 0';
            } else {
                $ifCondition = 'COUNT(*) = 0';
            }
            $select->reset(\Magento\Framework\DB\Select::COLUMNS);

            $condition = $this->getResource()->getConnection()->getCheckSql($ifCondition, '1', '0');
            $select->columns(new \Zend_Db_Expr($condition));

            if ($attribute->isStatic()) {
                $select->where("main.{$attribute->getAttributeCode()} > 0");
            } else {
                $select->where('main.attribute_id = ?', $attribute->getId());
            }
        }

        if ($this->configShare->isWebsiteScope()) {
            $entityTable = $attribute->getEntity()->getEntityTable();
            $select->join(
                ['entity_table' => $entityTable],
                'main.entity_id = entity_table.entity_id',
                []
            );
            $select->where('entity_table.website_id = ?', $website);
        }

        return $select;
    }

    /**
     * Process condition operator by attribute type and return mapped operator value
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return string
     */
    private function getMappedOperator(\Magento\Eav\Model\Entity\Attribute $attribute)
    {
        $operator = $this->getOperator();

        if ($attribute->getFrontendInput() == 'date'
            && $operator == '=='
        ) {
            return 'between';
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && $operator == '=='
        ) {
            return 'finset';
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && $operator == '!='
        ) {
            return '!finset';
        }

        return $operator;
    }

    /**
     * Process attribute value by attribute type and return mapped attribute value
     *
     * @param \Magento\Eav\Model\Entity\Attribute $attribute
     * @return array
     */
    private function getMappedValue(\Magento\Eav\Model\Entity\Attribute $attribute)
    {
        $operator = $this->getOperator();
        $value = $this->getValue();

        if ($attribute->getFrontendInput() == 'date'
            && $operator == '=='
        ) {
            $dateObj = (new \DateTime($this->getValue()))->setTime(0, 0, 0);
            $value = [
                'start' => $dateObj->format('Y-m-d H:i:s'),
                'end' => $dateObj->modify('+1 day')->format('Y-m-d H:i:s'),
            ];
        } elseif ($attribute->getFrontendInput() == 'multiselect'
            && is_array($value)
        ) {
            array_walk(
                $value,
                function (&$value) {
                    $value = (int)$value;
                }
            );
        }

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getValue()
    {
        if ($this->getInputType() === 'date'
            && $this->getOperator() === '<='
            && !$this->getIsValueParsed()
        ) {
            $date = (new \DateTime($this->getData('value')))->setTime(23, 59, 59);
            $value = $date->format('Y-m-d H:i:s');
            $this->setValue($value);
            $this->setIsValueParsed(true);
            return $this->getData('value');
        }
        return parent::getValue();
    }

    /**
     * Return customer select according to condition.
     *
     * @param int $websiteId
     * @return \Magento\Framework\DB\Select
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getCustomerSelect($websiteId)
    {
        $select = $this->getResource()->createSelect();
        $table = $this->getResource()->getTable('customer_entity');
        $select->from(['root' => $table], ['entity_id']);
        $select->where($this->getCustomerConditionalSql(null, $websiteId, true));
        return $select;
    }
}
