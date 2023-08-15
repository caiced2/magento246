<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Fixture;

use Magento\CustomerSegment\Model\Segment\Condition\Product\Combine;
use Magento\Framework\DataObject;

class ProductConditions extends Conditions
{
    public const DEFAULT_DATA = [
        'type' => Combine::class,
    ];

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Conditions::DEFAULT_DATA.
     * - $data['conditions']: An array of any:
     *      - ProductConditions
     *      - ProductCondition
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply($this->prepareData($data));
    }

    /**
     * Prepare conditions data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $conditions = [];
        $data = array_merge(self::DEFAULT_DATA, $data);

        foreach ($data['conditions'] as $condition) {
            $conditionData = $condition instanceof DataObject ? $condition->toArray() : $condition;
            if (!isset($condition['conditions'])) {
                $conditionData += ProductCondition::DEFAULT_DATA;
            }
            $conditions[] = $conditionData;
        }
        $data['conditions'] = $conditions;
        return $data;
    }
}
