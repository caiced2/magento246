<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\TargetRule\Model\Actions\Condition\Combine;

class Actions extends Conditions
{
    public const DEFAULT_DATA = [
        'type' => Combine::class,
    ];

    /**
     * @inheritdoc
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply(array_merge(self::DEFAULT_DATA, $data));
    }
}
