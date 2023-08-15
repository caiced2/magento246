<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Fixture;

use Magento\CustomerSegment\Model\Segment\Condition\Combine;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class Conditions implements DataFixtureInterface
{
    public const DEFAULT_DATA = [
        'type' => Combine::class,
        'attribute' => null,
        'operator' => null,
        'value' => '1',
        'aggregator' => 'all',
        'is_value_processed' => null,
        'conditions' => [],
    ];

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(DataObjectFactory  $dataObjectFactory)
    {
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as Conditions::DEFAULT_DATA.
     * - $data['conditions']: An array of any:
     *      - Conditions
     *      - ProductHistoryCondition
     */
    public function apply(array $data = []): ?DataObject
    {
        return $this->dataObjectFactory->create(['data' => $this->prepareData($data)]);
    }

    /**
     * Prepare conditions data
     *
     * @param array $data
     * @return array
     */
    private function prepareData(array $data): array
    {
        $data = array_merge(self::DEFAULT_DATA, $data);
        $data['conditions'] = $data['conditions'] ?? [];

        foreach ($data['conditions'] as $key => $condition) {
            $data['conditions'][$key] = $condition instanceof DataObject ? $condition->toArray() : $condition;
        }

        return $data;
    }
}
