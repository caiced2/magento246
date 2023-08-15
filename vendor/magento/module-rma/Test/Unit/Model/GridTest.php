<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Rma\Model\Grid;
use Magento\Rma\Model\Rma\Source\Status;
use Magento\Rma\Model\Rma\Source\StatusFactory;
use PHPUnit\Framework\TestCase;

class GridTest extends TestCase
{
    const TEST_STATUS = 'test_pending';

    /**
     * @var Grid
     */
    protected $rmaGrid;

    /**
     * @var StatusFactory|\PHPUnit_Framework_MockObject
     */
    protected $statusFactoryMock;

    /**
     * @var Context|\PHPUnit_Framework_MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject
     */
    protected $registryMock;

    /**
     * @var AbstractResource|\PHPUnit_Framework_MockObject
     */
    protected $resourceMock;

    /**
     * @var AbstractDb|\PHPUnit_Framework_MockObject
     */
    protected $resourceCollectionMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->statusFactoryMock = $this->createPartialMock(
            StatusFactory::class,
            ['create']
        );
        $this->resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName'])
            ->getMockForAbstractClass();
        $this->resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = ['status' => static::TEST_STATUS];
        $this->rmaGrid = new Grid(
            $this->contextMock,
            $this->registryMock,
            $this->statusFactoryMock,
            $this->resourceMock,
            $this->resourceCollectionMock,
            $data
        );
    }

    public function testGetStatusLabel()
    {
        $sourceStatus = $this->createPartialMock(Status::class, ['getItemLabel']);
        $this->statusFactoryMock->expects($this->once())->method('create')->willReturn($sourceStatus);
        $sourceStatus->expects($this->any())
            ->method('getItemLabel')
            ->willReturn(static::TEST_STATUS);

        $this->assertEquals(static::TEST_STATUS, $this->rmaGrid->getStatusLabel());
    }
}
