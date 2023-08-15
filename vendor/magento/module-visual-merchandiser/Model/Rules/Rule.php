<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules;

use \Magento\VisualMerchandiser\Model\Rules\RuleInterface;

/**
 * Class Rule
 * @package Magento\VisualMerchandiser\Model\Rules
 * @api
 * @since 100.0.2
 */
abstract class Rule extends \Magento\Framework\DataObject implements RuleInterface
{
    /**
     * @var array
     */
    protected $_rule = [];

    /**
     * @var \Magento\Eav\Model\Entity\Attribute
     */
    protected $_attribute;

    /**
     * @var array
     */
    protected $notices = [];

    /**
     * @param array $rule
     * @param \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
     */
    public function __construct(
        $rule,
        \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute
    ) {
        parent::__construct();
        $this->_rule = $rule;
        $this->_attribute = $attribute;
    }

    /**
     * @return array
     */
    public function getNotices()
    {
        return $this->notices;
    }

    /**
     * @return bool
     */
    public function hasNotices()
    {
        return !empty($this->getNotices());
    }

    /**
     * @return array
     */
    public static function getOperators()
    {
        return [
            'eq' => __('Equal'),
            'neq' => __('Not equal'),
            'gt' => __('Greater than'),
            'gteq' => __('Greater than or equal to'),
            'lt' => __('Less than'),
            'lteq' => __('Less than or equal to'),
            'like' => __('Contains')
        ];
    }

    /**
     * Get mapped SQL expression for operator
     *
     * @param string $operator
     *
     * @return mixed
     */
    public function getOperatorExpression($operator)
    {
        $operatorToExpressionMap = [
            'eq' => '= ?',
            'neq' => '!= ?',
            'gt' => '> ?',
            'gteq' => '>= ?',
            'lt' => '< ?',
            'lteq' => '<= ?',
            'like' => "LIKE '%?%'",
        ];
        if (!isset($operatorToExpressionMap[$operator])) {
            throw new \InvalidArgumentException("Operator {$operator} does not have mapped SQL expression");
        }
        return $operatorToExpressionMap[$operator];
    }

    /**
     * @param string $operator
     * @return bool
     */
    protected function isOperatorAllowed($operator)
    {
        return in_array($operator, array_keys($this->getOperators()));
    }

    /**
     * @param array $options
     * @return array
     */
    protected function toMappedOptions($options)
    {
        $mapped = [];
        foreach ($options as $opt) {
            if (isset($opt['value']) && !empty($opt['value'])) {
                $mapped[strtolower($opt['label'])] = $opt['value'];
            }
        }
        return $mapped;
    }

    /**
     * @return \Magento\VisualMerchandiser\Model\Rules\RuleInterface
     */
    public function get()
    {
        $operator = $this->_rule['operator'];

        if (!$this->isOperatorAllowed($operator)) {
            $attribute = $this->_rule['attribute'];
            throw new \RuntimeException("Operator not supported '{$operator}' for attribute '{$attribute}'");
        }

        return $this;
    }
}
