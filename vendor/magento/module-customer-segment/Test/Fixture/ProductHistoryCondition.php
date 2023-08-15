<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerSegment\Test\Fixture;

use Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History;
use Magento\Framework\DataObject;

class ProductHistoryCondition extends ProductConditions
{
    public const DEFAULT_DATA = [
        'type' => History::class,
        'operator' => '==',
        'value' => 'ordered_history'
    ];

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply(array_merge(self::DEFAULT_DATA, $data));
    }
}
