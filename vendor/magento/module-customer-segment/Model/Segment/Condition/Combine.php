<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Segment\Condition;

use Magento\CustomerSegment\Model\Condition\Combine\AbstractCombine;

/**
 * Segment conditions container
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Combine extends AbstractCombine
{
    /**
     * @param \Magento\Rule\Model\Condition\Context $context
     * @param \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment
     * @param array $data
     */
    public function __construct(
        \Magento\Rule\Model\Condition\Context $context,
        \Magento\CustomerSegment\Model\ConditionFactory $conditionFactory,
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceSegment,
        array $data = []
    ) {
        parent::__construct($context, $conditionFactory, $resourceSegment, $data);
        $this->setType(\Magento\CustomerSegment\Model\Segment\Condition\Combine::class);
    }

    /**
     * Get inherited conditions selectors
     *
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = [
            // Subconditions combo
            [
                'value' => \Magento\CustomerSegment\Model\Segment\Condition\Combine::class,
                'label' => __('Conditions Combination'),
                'available_in_guest_mode' => true
            ],
            // Customer address combo
            [
                'value' => \Magento\CustomerSegment\Model\Segment\Condition\Customer\Address::class,
                'label' => __('Customer Address')
            ],
            // Customer attribute group
            $this->_conditionFactory->create('Customer')->getNewChildSelectOptions(),
            // Shopping cart group
            $this->_conditionFactory->create('Shoppingcart')->getNewChildSelectOptions(),
            [
                'value' => [
                    // Product list combo
                    [
                        'value' => \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\ListCombine::class,
                        'label' => __('Product List'),
                        'available_in_guest_mode' => true
                    ],
                    // Product history combo
                    [
                        'value' => \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History::class,
                        'label' => __('Product History'),
                        'available_in_guest_mode' => true
                    ],
                ],
                'label' => __('Products'),
                'available_in_guest_mode' => true
            ],
            // Sales group
            $this->_conditionFactory->create('Sales')->getNewChildSelectOptions(),
        ];
        $conditions = array_merge_recursive(parent::getNewChildSelectOptions(), $conditions);
        return $this->_prepareConditionAccordingApplyToValue($conditions);
    }

    /**
     * Prepare base condition select which related with current condition combine
     *
     * @param Customer|\Zend_Db_Expr $customer
     * @param int|\Zend_Db_Expr $website $website
     * @param bool $isFiltered
     * @return \Magento\Framework\DB\Select
     */
    protected function _prepareConditionsSql($customer, $website, $isFiltered = true)
    {
        $select = parent::_prepareConditionsSql($customer, $website, $isFiltered);
        if ($isFiltered) {
            $select->limit(1);
        }
        return $select;
    }

    /**
     * Prepare Condition According to ApplyTo Value
     *
     * @param array $conditions
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return array
     */
    protected function _prepareConditionAccordingApplyToValue(array $conditions)
    {
        $returnedConditions = null;
        switch ($this->getRule()->getApplyTo()) {
            case \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS:
                $returnedConditions = $this->_removeUnnecessaryConditions($conditions);
                break;

            case \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS_AND_REGISTERED:
                $returnedConditions = $this->_markConditions($conditions);
                break;

            case \Magento\CustomerSegment\Model\Segment::APPLY_TO_REGISTERED:
                $returnedConditions = $conditions;
                break;

            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong "ApplyTo" type'));
                break;
        }
        return $returnedConditions;
    }

    /**
     * Remove unnecessary conditions
     *
     * @param array $conditionsList
     * @return array
     */
    protected function _removeUnnecessaryConditions(array $conditionsList)
    {
        $conditionResult = $conditionsList;
        foreach ($conditionResult as $key => $condition) {
            if ($key == 0 && isset($condition['value']) && $condition['value'] == '') {
                continue;
            }
            if (array_key_exists('available_in_guest_mode', $condition) && $condition['available_in_guest_mode']) {
                if (is_array($conditionResult[$key]['value'])) {
                    $conditionResult[$key]['value'] = $this->_removeUnnecessaryConditions($condition['value']);
                }
            } else {
                unset($conditionResult[$key]);
            }
        }
        return $conditionResult;
    }

    /**
     * Mark condition with asterisk
     *
     * @param array $conditionsList
     * @return array
     */
    protected function _markConditions(array $conditionsList)
    {
        $conditionResult = $conditionsList;
        foreach ($conditionResult as $key => $condition) {
            if (array_key_exists('available_in_guest_mode', $condition) && $condition['available_in_guest_mode']) {
                $conditionResult[$key]['label'] .= '*';
                if (is_array($conditionResult[$key]['value'])) {
                    $conditionResult[$key]['value'] = $this->_markConditions($condition['value']);
                }
            }
        }
        return $conditionResult;
    }
}
