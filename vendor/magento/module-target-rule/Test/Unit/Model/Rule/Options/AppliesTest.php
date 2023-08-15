<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Model\Rule\Options;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TargetRule\Model\Rule;
use Magento\TargetRule\Model\Rule\Options\Applies;
use PHPUnit\Framework\TestCase;

class AppliesTest extends TestCase
{
    /**
     * Tested model
     *
     * @var Applies
     */
    protected $_applies;

    protected function setUp(): void
    {
        $rule = $this->createMock(Rule::class);

        $rule->expects($this->once())
            ->method('getAppliesToOptions')
            ->willReturn([1, 2]);

        $this->_applies = (new ObjectManager($this))->getObject(
            Applies::class,
            [
                'targetRuleModel' => $rule,
            ]
        );
    }

    public function testToOptionArray()
    {
        $this->assertEquals([1, 2], $this->_applies->toOptionArray());
    }
}
