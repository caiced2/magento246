<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogRuleStaging\Test\Unit\Model\Rule;

use Magento\Backend\App\Action\Context;
use Magento\CatalogRule\Model\Rule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\CatalogRuleStaging\Model\Rule\Hydrator;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Staging\Model\Entity\RetrieverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HydratorTest extends TestCase
{
    /** @var Context|MockObject */
    protected $context;

    /** @var ManagerInterface|MockObject */
    protected $eventManager;

    /** @var \Magento\Framework\Message\ManagerInterface|MockObject */
    protected $messageManager;

    /** @var RetrieverInterface|MockObject */
    protected $entityRetriever;

    /** @var RequestInterface|MockObject */
    protected $request;

    /** @var RuleFactory|MockObject */
    protected $ruleFactory;

    /** @var Rule|MockObject */
    protected $rule;

    /** @var Hydrator */
    protected $hydrator;

    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->entityRetriever = $this->getMockBuilder(RetrieverInterface::class)
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->getMockForAbstractClass();
        $this->messageManager = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rule = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->hydrator = new Hydrator($this->context, $this->ruleFactory, $this->entityRetriever);
    }

    public function testHydrate()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId,
            'is_active' => 1,
            'rule' => [
                'conditions' => '',
            ],
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
                'adminhtml_controller_catalogrule_prepare_save',
                ['request' => $this->request]
            );
        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
            ->method('validateData')
            ->with(new DataObject($data))
            ->willReturn(true);
        $this->entityRetriever
            ->expects($this->once())
            ->method('getEntity')
            ->with($ruleId)
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
            ->method('loadPost')
            ->with([
                'rule_id' => $ruleId,
                'is_active' => 1,
                'conditions' => '',
            ])
            ->willReturnSelf();
        $this->assertSame($this->rule, $this->hydrator->hydrate($data));
    }

    public function testHydrateWithInvalidData()
    {
        $ruleId = 1;
        $data = [
            'rule_id' => $ruleId,
            'is_active' => 1,
            'rule' => [
                'conditions' => '',
            ],
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
                'adminhtml_controller_catalogrule_prepare_save',
                ['request' => $this->request]
            );
        $this->ruleFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->rule);
        $this->rule->expects($this->once())
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
