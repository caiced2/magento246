<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\App\Config\Value;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Helper\Data;
use Magento\PersistentHistory\Model\Adminhtml\System\Config\Cart;
use Magento\PersistentHistory\Observer\UpdateOptionCustomerSegmentationObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateOptionCustomerSegmentationObserverTest extends TestCase
{
    /**
     * @var MockObject
     */
    protected $valueFactoryMock;

    /**
     * @var UpdateOptionCustomerSegmentationObserver
     */
    protected $subject;

    protected function setUp(): void
    {
        /** @var ObjectManager  */
        $objectManager = new ObjectManager($this);
        $this->valueFactoryMock = $this->createPartialMock(
            ValueFactory::class,
            ['create']
        );
        $this->subject = $objectManager->getObject(
            UpdateOptionCustomerSegmentationObserver::class,
            ['valueFactory' => $this->valueFactoryMock]
        );
    }

    public function testUpdateOptionIfEventValueIsNull()
    {
        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $eventDataObjectMock = $this->getMockBuilder(Cart::class)
            ->addMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventDataObjectMock->expects($this->once())
            ->method('getValue')
            ->willReturn(null);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($eventDataObjectMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $this->subject->execute($observerMock);
    }

    public function testUpdateOptionSuccess()
    {
        $scopeId = 1;
        $scope = ['scope' => 'scope_value'];

        $observerMock = $this->createPartialMock(Observer::class, ['getEvent']);
        $eventDataObjectMock = $this->getMockBuilder(Cart::class)
            ->addMethods(['getValue', 'getScope', 'getScopeId'])
            ->disableOriginalConstructor()
            ->getMock();

        $eventDataObjectMock->expects($this->once())
            ->method('getValue')
            ->willReturn('value');
        $eventDataObjectMock->expects($this->once())
            ->method('getScope')
            ->willReturn($scope);
        $eventDataObjectMock->expects($this->once())
            ->method('getScopeId')
            ->willReturn($scopeId);

        $eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getDataObject'])
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock->expects($this->once())->method('getDataObject')->willReturn($eventDataObjectMock);

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);

        $valueMock = $this->getMockBuilder(Value::class)
            ->addMethods(['setScope', 'setScopeId', 'setValue', 'setPath'])
            ->onlyMethods(['save'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueFactoryMock->expects($this->once())->method('create')->willReturn($valueMock);

        $valueMock->expects($this->once())->method('setScope')->with($scope)->willReturnSelf();
        $valueMock->expects($this->once())->method('setScopeId')->with($scopeId)->willReturnSelf();
        $valueMock->expects($this->once())->method('setValue')->with(true)->willReturnSelf();
        $valueMock->expects($this->once())->method('save')->willReturnSelf();
        $valueMock->expects($this->once())->method('setPath')
            ->with(Data::XML_PATH_PERSIST_CUSTOMER_AND_SEGM)->willReturnSelf();

        $this->subject->execute($observerMock);
    }
}
