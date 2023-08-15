<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Checkout\Model\Session;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\EntityFactory;
use Magento\GiftRegistry\Model\GiftRegistryConfigProvider;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GiftRegistryConfigProviderTest extends TestCase
{
    /**
     * @var MockObject|Data
     */
    protected $helper;

    /**
     * @var MockObject|Session
     */
    protected $session;

    /**
     * @var MockObject|Entity
     */
    protected $entity;

    /**
     * @var GiftRegistryConfigProvider
     */
    protected $model;

    /**
     * Initialize testable object
     */
    protected function setUp(): void
    {
        $this->helper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFactory = $this->getMockBuilder(EntityFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->entity = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getShippingAddress', 'loadByEntityItem'])
            ->getMock();

        $entityFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->entity);

        /**
         * @var EntityFactory $entityFactory
         */
        $this->model = new GiftRegistryConfigProvider(
            $this->helper,
            $this->session,
            $entityFactory
        );
    }

    /**
     * @test
     */
    public function testGetConfig()
    {
        $quoteId = 'quoteId#1';
        $entityId = 'entityId#1';
        $isShipping = true;
        $giftregistryItemId = 'getGiftregistryItemId#1';
        $available = true;

        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $quoteItem = $this->getMockBuilder(Item::class)
            ->setMethods(['getGiftregistryItemId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->willReturn($available);
        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($quoteId);
        $this->session->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $quote->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$quoteItem]);
        $quoteItem->expects($this->once())
            ->method('getGiftregistryItemId')
            ->willReturn($giftregistryItemId);
        $this->entity->expects($this->once())
            ->method('loadByEntityItem')
            ->with($giftregistryItemId)
            ->willReturnSelf();
        $this->entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);
        $this->entity->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($isShipping);
        $quoteItem->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->assertEquals(
            [
                'giftRegistry' => [
                    'available' => $available,
                    'id' => $giftregistryItemId
                ]
            ],
            $this->model->getConfig()
        );
    }

    /**
     * @test
     */
    public function testGetConfigNegative()
    {
        $available = false;

        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->willReturn($available);
        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(null);

        $this->assertEquals(
            [
                'giftRegistry' => [
                    'available' => $available,
                    'id' => false
                ]
            ],
            $this->model->getConfig()
        );
    }
}
