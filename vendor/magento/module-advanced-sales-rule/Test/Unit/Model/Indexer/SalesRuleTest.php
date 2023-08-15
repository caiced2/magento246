<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdvancedSalesRule\Test\Unit\Model\Indexer;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Full;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\FullFactory;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\Rows;
use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\RowsFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SalesRuleTest extends TestCase
{
    /**
     * @var SalesRule
     */
    protected $model;

    /**
     * @var MockObject
     */
    protected $fullActionFactory;

    /**
     * @var MockObject
     */
    protected $rowsActionFactory;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $className = FullFactory::class;
        /** @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Action\FullFactory fullActionFactory */
        $this->fullActionFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $className = RowsFactory::class;
        $this->rowsActionFactory = $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->model = $this->objectManager->getObject(
            SalesRule::class,
            [
                'fullActionFactory' => $this->fullActionFactory,
                'rowsActionFactory' => $this->rowsActionFactory,
            ]
        );
    }

    /**
     * test Execute
     */
    public function testExecute()
    {
        $ids = [1, 2, 3];

        $className = Rows::class;
        $rowsAction = $this->createMock($className);

        $this->rowsActionFactory->expects($this->any())
            ->method('create')
            ->willReturn($rowsAction);

        $rowsAction->expects($this->once())
            ->method('execute')
            ->with($ids);

        $this->model->execute($ids);
    }

    /**
     * test ExecuteFull
     */
    public function testExecuteFull()
    {
        $className = Full::class;
        $fullAction = $this->createMock($className);

        $this->fullActionFactory->expects($this->any())
            ->method('create')
            ->willReturn($fullAction);

        $fullAction->expects($this->once())
            ->method('execute')
            ->willReturnSelf();

        $this->model->executeFull();
    }

    /**
     * test ExecuteList
     */
    public function testExecuteList()
    {
        $ids = [1, 2, 3];

        $className = Rows::class;
        $rowsAction = $this->createMock($className);

        $this->rowsActionFactory->expects($this->any())
            ->method('create')
            ->willReturn($rowsAction);

        $rowsAction->expects($this->once())
            ->method('execute')
            ->with($ids);

        $this->model->executeList($ids);
    }

    /**
     * test ExecuteRow
     */
    public function testExecuteRow()
    {
        $id = 1;
        $ids = [$id];

        $className = Rows::class;
        $rowsAction = $this->createMock($className);

        $this->rowsActionFactory->expects($this->any())
            ->method('create')
            ->willReturn($rowsAction);

        $rowsAction->expects($this->once())
            ->method('execute')
            ->with($ids);

        $this->model->executeRow($id);
    }
}
