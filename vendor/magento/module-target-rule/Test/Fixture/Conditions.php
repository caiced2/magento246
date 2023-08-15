<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\TargetRule\Model\Rule\Condition\Combine;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class Conditions implements DataFixtureInterface
{
    public const DEFAULT_DATA = [
        'type' => Combine::class,
        'attribute' => null,
        'operator' => null,
        'value' => '1',
        'is_value_processed' => null,
        'aggregator' => 'all',
        'conditions' => [

        ],
    ];

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        DataObjectFactory  $dataObjectFactory
    ) {
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Conditions::DEFAULT_DATA.
     * - $data['conditions']: An array of condition fixture data (Condition::DEFAULT_DATA)
     */
    public function apply(array $data = []): ?DataObject
    {
        return $this->dataObjectFactory->create(['data' => $this->prepareData($data)]);
    }

    /**
     * Prepare combine data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $data['conditions'] = $this->prepareConditionsData($data);

        return $data;
    }

    /**
     * Prepare conditions data
     *
     * @param array $data
     * @return array
     */
    private function prepareConditionsData(array $data): array
    {
        $conditions = [];

        foreach ($data['conditions'] as $condition) {
            $conditionData = $condition instanceof DataObject ? $condition->toArray() : $condition;
            $conditionData += Condition::DEFAULT_DATA;
            $conditions[] = $conditionData;
        }

        return $conditions;
    }
}
