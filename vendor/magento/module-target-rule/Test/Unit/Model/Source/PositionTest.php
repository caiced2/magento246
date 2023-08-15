<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\Source\Position;
use PHPUnit\Framework\TestCase;

class PositionTest extends TestCase
{
    /**
     * @var Rotation
     */
    protected $_rotation;

    protected function setUp(): void
    {
        $this->_rotation = (new ObjectManager($this))->getObject(Position::class, []);
    }

    public function testSetType()
    {
        $result = [
            Rule::BOTH_SELECTED_AND_RULE_BASED => __('Both Selected and Rule-Based'),
            Rule::SELECTED_ONLY => __('Selected Only'),
            Rule::RULE_BASED_ONLY => __('Rule-Based Only'),
        ];
        $this->assertEquals($result, $this->_rotation->toOptionArray());
    }
}
