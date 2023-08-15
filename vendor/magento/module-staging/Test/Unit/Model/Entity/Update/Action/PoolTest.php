<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

use Magento\Framework\ObjectManagerInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\Staging\Model\Entity\Update\Action\ActionInterface;
use Magento\Staging\Model\Entity\Update\Action\Pool;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutorInterface;
use Magento\Staging\Model\Entity\Update\Action\TransactionPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
    /** @var array */
    private $actions = [
        RuleInterface::class => [
            'save' => [
                'save' => 'ruleUpdateSaveSaveAction',
                'assign' => 'ruleUpdateSaveAssignAction',
            ]
        ]
    ];

    /** @var MockObject */
    private $transactionPool;

    /** @var ObjectManager|MockObject */
    private $objectManager;

    /**
     * @var Pool
     */
    protected $pool;

    protected function setUp(): void
    {
        $this->transactionPool = $this->getMockBuilder(
            TransactionPool::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = $this->getMockBuilder(ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->pool = new Pool(
            $this->transactionPool,
            $this->objectManager,
            $this->actions
        );
    }

    public function testGetExecutorNotExistsInPool()
    {
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->assertEquals($action, $this->pool->getExecutor($action));
    }

    public function testGetExecutor()
    {
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $executor = $this->getMockBuilder(
            TransactionExecutorInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $executor->expects($this->once())
            ->method('setAction');
        $this->transactionPool->expects($this->once())
            ->method('getExecutor')
            ->willReturn($executor);
        $this->assertEquals($executor, $this->pool->getExecutor($action));
    }

    public function testGetAction()
    {
        $entityType = RuleInterface::class;
        $namespace = 'save';
        $actionType = 'assign';
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->objectManager->expects($this->once())
            ->method('get')
            ->with($this->actions[$entityType][$namespace][$actionType])->willReturn($action);
        $this->assertEquals($action, $this->pool->getAction($entityType, $namespace, $actionType));
    }
}
