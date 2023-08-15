<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Test\Unit\Model\Entity\Update\Action;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Staging\Model\Entity\Update\Action\ActionInterface;
use Magento\Staging\Model\Entity\Update\Action\TransactionExecutor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionExecutorTest extends TestCase
{
    /** @var MockObject */
    private $resourceConnection;

    /**
     * @var TransactionExecutor
     */
    protected $transactionExecutor;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transactionExecutor = new TransactionExecutor(
            $this->resourceConnection
        );
    }

    public function testSetAction()
    {
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->assertNull($this->transactionExecutor->setAction($action));
    }

    public function testExecuteNoAction()
    {
        $this->expectException('LogicException');
        $this->transactionExecutor->execute([]);
    }

    public function testExecuteRollback()
    {
        $this->expectException('Exception');
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->expects($this->once())
            ->method('execute')
            ->with([])
            ->willThrowException(new \Exception('Error during save'));

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnection->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);

        $adapterMock->expects($this->once())
            ->method('rollBack');

        $this->transactionExecutor->setAction($action);
        $this->transactionExecutor->execute([]);
    }

    public function testExecute()
    {
        $action = $this->getMockBuilder(ActionInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $action->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn(true);

        $adapterMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceConnection->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapterMock);
        $adapterMock->expects($this->once())
            ->method('commit');

        $this->transactionExecutor->setAction($action);
        $this->transactionExecutor->execute([]);
    }
}
