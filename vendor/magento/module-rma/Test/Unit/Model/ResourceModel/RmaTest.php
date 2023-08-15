<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Rma\Model\Grid;
use Magento\Rma\Model\GridFactory;
use Magento\Rma\Model\ResourceModel\Rma;
use Magento\Rma\Model\Rma\Create as RmaCreate;
use Magento\Sales\Model\Order;
use Magento\SalesSequence\Model\Manager;
use Magento\SalesSequence\Model\Sequence;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RmaTest extends TestCase
{
    /**
     * @var Rma
     */
    protected $rma;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var GridFactory|MockObject
     */
    protected $gridFactory;

    /**
     * @var Manager|MockObject
     */
    protected $sequenceManager;

    /**
     * @var ResourceConnection|MockObject
     */
    protected $resourceMock;

    /**
     * @var Mysql|MockObject
     */
    protected $connectionMock;

    /**
     * @var \Magento\Rma\Model\Rma|MockObject
     */
    protected $rmaMock;

    /**
     * @var Sequence|MockObject
     */
    protected $sequenceMock;

    /**
     * @var Grid|MockObject
     */
    protected $gridModelMock;

    /**
     * @var ObjectRelationProcessor|MockObject
     */
    protected $objectRelationProcessorMock;

    /**
     * @var RmaCreate|MockObject
     */
    private $rmaCreate;

    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->gridFactory = $this->getMockBuilder(GridFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->sequenceManager = $this->createMock(Manager::class);
        $this->connectionMock = $this->getMockBuilder(Mysql::class)
            ->onlyMethods(
                [
                    'describeTable',
                    'insert',
                    'lastInsertId',
                    'beginTransaction',
                    'commit',
                    'quoteInto',
                    'update',
                    'rollback'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->objectRelationProcessorMock = $this->createMock(
            ObjectRelationProcessor::class
        );
        $this->sequenceMock = $this->createMock(Sequence::class);
        $this->gridModelMock = $this->createMock(Grid::class);
        $this->gridFactory->expects($this->once())->method('create')->willReturn($this->gridModelMock);
        $this->rmaMock = $this->createMock(\Magento\Rma\Model\Rma::class);
        $this->context->expects($this->once())->method('getResources')->willReturn($this->resourceMock);
        $this->context->expects($this->once())
            ->method('getObjectRelationProcessor')
            ->willReturn($this->objectRelationProcessorMock);
        $this->rmaCreate = $this->getMockBuilder(RmaCreate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->rma = $this->objectManagerHelper->getObject(
            Rma::class,
            [
                'context' => $this->context,
                'rmaGridFactory' => $this->gridFactory,
                'sequenceManager' => $this->sequenceManager,
                'rmaCreate' => $this->rmaCreate
            ]
        );
    }

    public function testSave()
    {
        $nextValue = 2;
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->any())
            ->method('quoteInto');
        $this->connectionMock->expects($this->any())
            ->method('describeTable')
            ->willReturn([]);
        $this->connectionMock->expects($this->any())
            ->method('update');
        $this->connectionMock->expects($this->any())
            ->method('lastInsertId');
        $this->rmaMock->expects($this->once())->method('isDeleted')->willReturn(false);
        $this->rmaMock->expects($this->once())->method('hasDataChanges')->willReturn(true);
        $this->rmaMock->expects($this->once())->method('validateBeforeSave');
        $this->rmaMock->expects($this->once())->method('beforeSave');
        $this->rmaMock->expects($this->once())->method('isSaveAllowed')->willReturn(true);
        $this->rmaMock->expects($this->atLeastOnce())->method('getData')->willReturn([]);
        $this->rmaMock->expects($this->once())->method('setData')->willReturn([]);
        $this->rmaMock->expects($this->once())->method('getIncrementId')->willReturn(false);
        $this->rmaMock->expects($this->once())->method('getStoreId')->willReturn(1);
        $this->rmaMock->expects($this->once())->method('setIncrementId')->with($nextValue);
        $this->rmaMock->expects($this->once())->method('getCustomerId')->willReturn(1);
        $this->rmaMock->expects($this->once())->method('getComments')->willReturn([]);
        $this->rmaMock->expects($this->once())->method('getTracks')->willReturn([]);
        $this->sequenceMock->expects($this->once())->method('getNextValue')->willReturn($nextValue);
        $this->sequenceManager->expects($this->once())
            ->method('getSequence')
            ->with('rma_item', 1)
            ->willReturn($this->sequenceMock);

        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaMock->method('getOrder')->willReturn($order);

        $this->rma->save($this->rmaMock);
    }
}
