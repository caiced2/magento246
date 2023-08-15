<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\TargetRule\Model\Rule\Condition\Product\Attributes;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class Condition implements DataFixtureInterface
{
    public const DEFAULT_DATA = [
        'type' => Attributes::class,
        'attribute' => null,
        'operator' => '==',
        'value' => null,
        'is_value_processed' => false,
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
     * @param array $data Parameters. Same format as Condition::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        return $this->dataObjectFactory->create(['data' => array_merge(self::DEFAULT_DATA, $data)]);
    }
}
