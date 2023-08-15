<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\ResourceModel;

/**
 * Resource model for customer segment helper
 */
class Helper extends \Magento\Framework\DB\Helper
{
    /**
     * Get comparison condition for rule condition operator which will be used in SQL query
     *
     * @param string $operator
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getSqlOperator($operator)
    {
        /*
            '{}'  => __('contains'),
            '!{}' => __('does not contain'),
            '()'  => __('is one of'),
            '!()' => __('is not one of'),
            requires custom selects
        */

        switch ($operator) {
            case '==':
                return '=';
            case '!=':
                return '<>';
            case '{}':
                return 'LIKE';
            case '!{}':
                return 'NOT LIKE';
            case '()':
                return 'IN';
            case '!()':
                return 'NOT IN';
            case '[]':
                return 'FIND_IN_SET(%s, %s)';
            case '![]':
                return 'FIND_IN_SET(%s, %s) IS NULL';
            case 'between':
                return 'BETWEEN %s AND %s';
            case 'finset':
            case '!finset':
                return $operator;
            case '>':
            case '<':
            case '>=':
            case '<=':
                return $operator;
            default:
                throw new \Magento\Framework\Exception\LocalizedException(__('Unknown operator specified.'));
        }
    }
}
