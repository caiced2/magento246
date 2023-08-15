<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesRuleStaging\Test\Unit\Model\Rule;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RuleFactory;
use Magento\SalesRuleStaging\Model\Rule\Hydrator;
use Magento\Staging\Model\Entity\RetrieverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    /** @var Hydrator */
    protected $hydrator;

    /** @var Context|MockObject */
    protected $context;

    /** @var RetrieverInterface|MockObject */
    protected $entityRetriever;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var \Magento\Framework\Message\ManagerInterface|MockObject */
    protected $messageManager;

    /** @var Rule|MockObject */
    protected $salesRule;

    /** @var RuleFactory|MockObject */
    protected $toModelConverter;
    /**
     * @var MockObject
     */
    private $ruleFactory;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRetriever = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->salesRule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['validateData', 'loadPost'])
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->hydrator = new Hydrator($this->context, $this->entityRetriever, $this->ruleFactory);
    }

    public function testHydrate()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId
        ];

        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'adminhtml_controller_salesrule_prepare_save',
                ['request' => $this->request]
            );
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->salesRule);
        $this->ruleFactory->expects(($this->once()))
            ->method('create')
            ->willReturn($this->salesRule);
        $this->salesRule->expects($this->once())
            ->method('validateData')
            ->with(new DataObject($data))
            ->willReturn(true);
        $this->salesRule->expects($this->once())
            ->method('loadPost')
            ->with($data)
            ->willReturnSelf();
        $this->assertSame($this->salesRule, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId
        ];

        $this->context->expects($this->once())
            ->method('getEventManager')
            ->willReturn($this->eventManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with(
                'adminhtml_controller_salesrule_prepare_save',
                ['request' => $this->request]
            );
        $this->entityRetriever->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->salesRule);
        $this->ruleFactory->expects(($this->once()))
            ->method('create')
            ->willReturn($this->salesRule);
        $this->salesRule->expects($this->once())
            ->method('validateData')
            ->with(new DataObject($data))
            ->willReturn([
                'Error message'
            ]);
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->messageManager->expects($this->once())
            ->method('addError')
            ->with('Error message');
        $this->assertFalse($this->hydrator->hydrate($data));
    }
}
