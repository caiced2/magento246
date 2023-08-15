<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Block\Customer\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\GiftRegistry\Block\Customer\Checkout\Multishipping;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MultishippingTest extends TestCase
{
    /**
     * @var Multishipping
     */
    protected $block;

    /**
     * @var MockObject
     */
    protected $contextMock;

    /**
     * @var MockObject
     */
    protected $customerSessionMock;

    /**
     * @var MockObject
     */
    protected $quoteMock;

    /**
     * @var MockObject
     */
    protected $entityFactoryMock;

    /**
     * @var MockObject
     */
    protected $entityMock;

    /**
     * @var MockObject
     */
    protected $itemMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(Context::class);
        $this->customerSessionMock = $this->createMock(Session::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->entityMock = $this->getMockBuilder(Entity::class)
            ->addMethods(['getShippingAddress'])
            ->onlyMethods(['loadByEntityItem', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->addMethods(['getGiftregistryItemId', 'getQuoteItem', 'getCustomerAddressId'])
            ->onlyMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityFactoryMock = $this->createPartialMock(
            EntityFactory::class,
            ['create']
        );
        $this->block = new Multishipping(
            $this->contextMock,
            $this->createMock(Data::class),
            $this->customerSessionMock,
            $this->entityFactoryMock
        );
    }

    public function testGetGiftregistrySelectedAddressesIndexes()
    {
        $item = [
            'entity_id' => 1,
            'item_id' => 'registryId',
            'is_address' => 1
        ];
        $this->customerSessionMock->expects($this->any())->method('getQuote')->willReturn($this->quoteMock);
        $this->customerSessionMock->expects($this->any())->method('getQuoteId')->willReturn(1);
        $this->entityFactoryMock->expects($this->once())->method('create')->willReturn($this->entityMock);
        $this->quoteMock->expects($this->once())->method('getItemsCollection')->willReturn([$this->itemMock]);
        $this->itemMock->expects($this->once())->method('getGiftregistryItemId')->willReturn('registryId');
        $this->entityMock->expects($this->once())->method('loadByEntityItem')->with('registryId')->willReturnSelf();
        $this->entityMock->expects($this->once())->method('getId')->willReturn($item['entity_id']);
        $this->entityMock->expects($this->once())->method('getShippingAddress')->willReturn(1);
        $this->itemMock->expects($this->once())->method('getId')->willReturn('itemId');
        $this->quoteMock
            ->expects($this->once())
            ->method('getShippingAddressesItems')
            ->willReturn([ 'index' => $this->itemMock]);
        $this->itemMock->expects($this->once())->method('getQuoteItem')->willReturn($this->quoteMock);
        $this->itemMock->expects($this->once())->method('getCustomerAddressId')->willReturn(null);
        $this->quoteMock->expects($this->once())->method('getId')->willReturn('itemId');
        $this->assertEquals(['index'], $this->block->getGiftregistrySelectedAddressesIndexes());
    }
}
