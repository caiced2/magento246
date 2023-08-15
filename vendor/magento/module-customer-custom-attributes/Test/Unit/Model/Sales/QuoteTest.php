<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Sales;

use Magento\Customer\Model\Attribute;
use Magento\CustomerCustomAttributes\Model\Sales\Quote;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteTest extends TestCase
{
    /**
     * @var Quote
     */
    protected $quote;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Registry|MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote|MockObject
     */
    protected $resourceMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $eventManagerMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->registryMock = $this->createMock(Registry::class);
        $this->resourceMock = $this->createMock(
            \Magento\CustomerCustomAttributes\Model\ResourceModel\Sales\Quote::class
        );

        $this->eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);

        $this->contextMock->expects($this->once())
            ->method('getEventDispatcher')
            ->willReturn($this->eventManagerMock);

        $this->quote = new Quote(
            $this->contextMock,
            $this->registryMock,
            $this->resourceMock
        );
    }

    public function testSaveNewAttribute()
    {
        $attributeMock = $this->createMock(Attribute::class);

        $this->resourceMock->expects($this->once())
            ->method('saveNewAttribute')
            ->with($attributeMock);

        $this->assertEquals($this->quote, $this->quote->saveNewAttribute($attributeMock));
    }

    public function testDeleteAttribute()
    {
        $attributeMock = $this->createMock(Attribute::class);

        $this->resourceMock->expects($this->once())
            ->method('deleteAttribute')
            ->with($attributeMock);

        $this->assertEquals($this->quote, $this->quote->deleteAttribute($attributeMock));
    }

    public function testAttachAttributeData()
    {
        $salesMock = $this->createMock(AbstractModel::class);
        $salesMock->expects($this->once())
            ->method('addData')
            ->with([]);

        $this->assertEquals($this->quote, $this->quote->attachAttributeData($salesMock));
    }

    public function testSaveAttributeData()
    {
        $salesMock = $this->createMock(AbstractModel::class);
        $salesMock->expects($this->once())
            ->method('getData')
            ->willReturn([]);
        $salesMock->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->resourceMock->expects($this->once())
            ->method('save')
            ->with($this->quote)->willReturnSelf();

        $this->assertEquals($this->quote, $this->quote->saveAttributeData($salesMock));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testBeforeSaveNegative()
    {
        $salesMock = $this->createMock(AbstractModel::class);
        $this->resourceMock->expects($this->once())
            ->method('isEntityExists')
            ->with($this->quote)
            ->willReturn(false);

        $this->quote->beforeSave();
        $this->assertFalse($this->quote->isSaveAllowed());
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function testBeforeSave()
    {
        $salesMock = $this->createMock(AbstractModel::class);
        $this->resourceMock->expects($this->once())
            ->method('isEntityExists')
            ->with($this->quote)
            ->willReturn(true);
        $this->eventManagerMock->expects($this->exactly(2))->method('dispatch');
        $this->quote->beforeSave();
        $this->assertTrue($this->quote->isSaveAllowed());
    }
}
