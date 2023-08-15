<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftWrapping\Test\Unit\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\GiftWrapping\Observer\CheckoutProcessWrappingInfo;
use Magento\GiftWrapping\Observer\ItemInfoManager;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CheckoutProcessWrappingInfoTest extends TestCase
{
    /** @var CheckoutProcessWrappingInfo */
    protected $_model;

    /**
     * @var DataObject
     */
    protected $_event;

    /**
     * @var MockObject
     */
    protected $itemInfoManager;

    /**
     * @var MockObject
     */
    protected $observerMock;

    /**
     * @var MockObject
     */
    protected $eventMock;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);
        $this->itemInfoManager = $this->createMock(ItemInfoManager::class);
        $this->observerMock = $this->createMock(Observer::class);
        $this->eventMock = $this->getMockBuilder(Event::class)
            ->addMethods(['getQuote', 'getItems', 'getOrder', 'getOrderItem', 'getQuoteItem'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->_model = $objectManagerHelper->getObject(
            CheckoutProcessWrappingInfo::class,
            [
                'itemInfoManager' =>  $this->itemInfoManager
            ]
        );
        $this->_event = new DataObject();
    }

    public function testCheckoutProcessWrappingInfoQuote()
    {
        $giftWrappingInfo = ['quote' => [1 => ['some data']]];
        $requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $quoteMock = $this->createMock(Quote::class);
        $event = new Event(['request' => $requestMock, 'quote' => $quoteMock]);
        $observer = new Observer(['event' => $event]);

        $requestMock->expects($this->once())
            ->method('getParam')
            ->with('giftwrapping')
            ->willReturn($giftWrappingInfo);

        $this->itemInfoManager->expects($this->once())->method('saveOrderInfo')->with($quoteMock, ['some data'])
            ->willReturnSelf();
//        $quoteMock->expects($this->once())->method('getShippingAddress')->will($this->returnValue(false));
//        $quoteMock->expects($this->once())->method('addData')->will($this->returnSelf());
        $quoteMock->expects($this->never())->method('getAddressById');
        $this->_model->execute($observer);
    }
}
