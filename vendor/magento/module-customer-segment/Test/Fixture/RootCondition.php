<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Fixture;

use Magento\CustomerSegment\Model\Segment\Condition\Combine\Root;
use Magento\Framework\DataObject;

class RootCondition extends Conditions
{
    public const DEFAULT_DATA = [
        'type' => Root::class,
        'attribute' => null,
        'operator' => null,
        'value' => '1',
        'aggregator' => 'all',
        'is_value_processed' => null,
        'conditions' => [],
    ];

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as RootCondition::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply(array_merge(self::DEFAULT_DATA, $data));
    }
}
