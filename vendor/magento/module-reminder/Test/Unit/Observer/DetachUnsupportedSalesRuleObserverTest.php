<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Reminder\Helper\Data;
use Magento\Reminder\Model\Rule;
use Magento\Reminder\Observer\DetachUnsupportedSalesRuleObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DetachUnsupportedSalesRuleObserverTest extends TestCase
{
    /**
     * @var DetachUnsupportedSalesRuleObserver
     */
    private $model;

    /**
     * @var \Magento\Reminder\Model\RuleFactory|MockObject
     */
    private $ruleFactory;

    /**
     * @var Rule|MockObject
     */
    private $rule;

    /**
     * @var Observer|MockObject
     */
    private $eventObserver;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule|MockObject
     */
    private $salesRule;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->rule = $this->getMockBuilder(Rule::class)
            ->setMethods(['sendReminderEmails', '__wakeup', 'detachSalesRule'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->salesRule = $this->getMockBuilder(\Magento\SalesRule\Model\ResourceModel\Rule::class)
            ->setMethods(['getCouponType', 'getUseAutoGeneration', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->ruleFactory = $this->getMockBuilder(\Magento\Reminder\Model\RuleFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory->expects($this->any())->method('create')->willReturn($this->rule);

        $this->eventObserver = $this->getMockBuilder(Observer::class)
            ->setMethods(['getCollection', 'getRule', 'getForm', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            DetachUnsupportedSalesRuleObserver::class,
            ['ruleFactory' => $this->ruleFactory]
        );
    }

    /**
     * @return void
     */
    public function testDetachUnsupportedSalesRule()
    {
        $this->salesRule
            ->expects($this->once())
            ->method('getCouponType')
            ->willReturn(\Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC);
        $this->salesRule->expects($this->once())->method('getUseAutoGeneration')->willReturn([1]);
        $this->salesRule->expects($this->once())->method('getId')->willReturn(1);
        $this->rule->expects($this->once())->method('detachSalesRule')->with(1);
        $this->eventObserver->expects($this->once())->method('getRule')->willReturn($this->salesRule);
        $this->model->execute($this->eventObserver);
    }
}
