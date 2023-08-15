<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Model\Rules\Rule;

class DaysAgo extends \Magento\VisualMerchandiser\Model\Rules\Rule
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $localeDate;

    /**
     * Get date for current scope
     *
     * @return \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     *
     * @deprecated 100.2.0
     */
    private function getLocaleDate()
    {
        if ($this->localeDate === null) {
            $this->localeDate = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class
            );
        }
        return $this->localeDate;
    }

    /**
     * Operators map for DaysAgo rule
     *
     * @var array
     */
    protected $operatorMap = [
        'lt' => 'gt',
        'gt' => 'lt',
        'gteq' => 'lteq',
        'lteq' => 'gteq'
    ];

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return void
     */
    public function applyToCollection($collection)
    {
        $value = (int)$this->_rule['value'];
        $this->_rule['operator'] = $this->getOperatorForRule($this->_rule['operator']);
        $currentDate = $this->getLocaleDate()->date();
        $dateValue = $currentDate->modify('-' . $value . ' days');
        $criteria = null;

        if ($this->_rule['operator'] == 'eq') {
            $dateStart = $this->getLocaleDate()->convertConfigTimeToUtc($dateValue->format('Y-m-d 00:00:00'));
            $dateEnd = $this->getLocaleDate()->convertConfigTimeToUtc($dateValue->format('Y-m-d 23:59:59'));
            $criteria = [
                'from'  => $dateStart,
                'to' => $dateEnd
            ];
        } elseif ($this->_rule['operator'] == 'gt') {
            $criteria = [
                $this->_rule['operator'] => $this->getLocaleDate()->convertConfigTimeToUtc(
                    $dateValue->format('Y-m-d 23:59:59')
                )
            ];
        } elseif ($this->_rule['operator'] == 'gteq') {
            $criteria = [
                $this->_rule['operator'] => $this->getLocaleDate()->convertConfigTimeToUtc(
                    $dateValue->format('Y-m-d 00:00:00')
                )
            ];
        } elseif ($this->_rule['operator'] == 'lt') {
            $criteria = [
                $this->_rule['operator'] => $this->getLocaleDate()->convertConfigTimeToUtc(
                    $dateValue->format('Y-m-d 00:00:00')
                )
            ];
        } elseif ($this->_rule['operator'] == 'lteq') {
            $criteria = [
                $this->_rule['operator'] => $this->getLocaleDate()->convertConfigTimeToUtc(
                    $dateValue->format('Y-m-d 23:59:59')
                )
            ];
        }
        $collection->addFieldToFilter($this->_rule['attribute'], $criteria);
    }

    /**
     * Return valid operator for rule
     *
     * @param string $operator
     * @return string
     */
    protected function getOperatorForRule($operator)
    {
        return isset($this->operatorMap[$operator]) ? $this->operatorMap[$operator] : $operator;
    }

    /**
     * @return array
     */
    public static function getOperators()
    {
        return [
            'eq' => __('Equal'),
            'gt' => __('Greater than'),
            'gteq' => __('Greater than or equal to'),
            'lt' => __('Less than'),
            'lteq' => __('Less than or equal to')
        ];
    }
}
