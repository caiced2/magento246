<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\TargetRule\Model\Actions\Condition\Product\Attributes;

class Action extends Condition
{
    public const DEFAULT_DATA = [
        'type' => Attributes::class,
        'value_type' => Attributes::VALUE_TYPE_CONSTANT
    ];

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply(array_merge(self::DEFAULT_DATA, $data));
    }
}
