<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Test\Unit\Block\Adminhtml\Rule;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Rule\Model\Condition\Combine;
use Magento\TargetRule\Block\Adminhtml\Rule\Conditions;
use Magento\TargetRule\Model\Rule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConditionsTest extends TestCase
{
    /** @var Conditions */
    private $conditions;

    /** @var MockObject AbstractElement */
    private $abstractElementMock;

    protected function setUp(): void
    {
        $this->conditions = new Conditions();
        $this->abstractElementMock = $this->getMockForAbstractClass(
            AbstractElement::class,
            [],
            '',
            false,
            false,
            true,
            ['getRule']
        );
    }

    /**
     * @covers \Magento\TargetRule\Block\Adminhtml\Rule\Conditions::render
     * returns conditions->asHtmlRecursive
     */
    public function testRenderReturnAsHtmlRecursive()
    {
        $htmlRecursive = 'HtmlRecursive';
        /* @var \PHPUnit\Framework\MockObject\MockObject $actionsMock */
        $conditionsMock = $this->createMock(Combine::class);
        /* @var \PHPUnit\Framework\MockObject\MockObject $ruleMock */
        $ruleMock = $this->createPartialMock(Rule::class, ['getConditions']);
        $this->abstractElementMock->expects($this->any())->method('getRule')->willReturn($ruleMock);
        $ruleMock->expects($this->any())->method('getConditions')->willReturn($conditionsMock);
        $conditionsMock->expects($this->any())->method('asHtmlRecursive')->willReturn($htmlRecursive);
        $this->assertEquals($htmlRecursive, $this->conditions->render($this->abstractElementMock));
    }

    /**
     * @covers \Magento\TargetRule\Block\Adminhtml\Rule\Conditions::render
     * returns empty when AbstractElement->getRule is null
     */
    public function testRenderReturnsEmptyWhenGetRuleIsNull()
    {
        $this->abstractElementMock->expects($this->any())->method('getRule')->willReturn(null);
        $this->assertEmpty($this->conditions->render($this->abstractElementMock));
    }

    /**
     * @covers \Magento\TargetRule\Block\Adminhtml\Rule\Conditions::render
     * returns empty when Rule->getConditions is null
     */
    public function testRenderReturnsEmptyWhenGetConditionsIsNull()
    {
        $ruleMock = $this->createPartialMock(Rule::class, ['getConditions']);
        $this->abstractElementMock->expects($this->any())->method('getRule')->willReturn($ruleMock);
        $ruleMock->expects($this->any())->method('getConditions')->willReturn(null);
        $this->assertEmpty($this->conditions->render($this->abstractElementMock));
    }
}
