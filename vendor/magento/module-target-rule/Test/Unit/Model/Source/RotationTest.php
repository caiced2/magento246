<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\Source\Rotation;
use PHPUnit\Framework\TestCase;

class RotationTest extends TestCase
{
    /**
     * @var Rotation
     */
    protected $_rotation;

    protected function setUp(): void
    {
        $this->_rotation = (new ObjectManager($this))->getObject(Rotation::class, []);
    }

    public function testToOptionArray()
    {
        $result = [
            Rule::ROTATION_NONE => __('By Priority, Then by ID'),
            Rule::ROTATION_SHUFFLE => __('By Priority, Then Random'),
            Rotation::ROTATION_WEIGHTED_RANDOM => __('Weighted Random'),
        ];
        $this->assertEquals($result, $this->_rotation->toOptionArray());
    }
}
