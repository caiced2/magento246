<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GiftRegistry\Test\Unit\Model\GuestCart;

use Magento\GiftRegistry\Api\ShippingMethodManagementInterface;
use Magento\GiftRegistry\Model\GuestCart\ShippingMethodManagement;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingMethodManagementTest extends TestCase
{
    /**
     * @var ShippingMethodManagement
     */
    private $model;

    /**
     * Shipping method management
     *
     * @var MockObject
     */
    private $methodManagementMock;

    /**
     * Quote ID mask factory
     *
     * @var MockObject
     */
    private $idMaskFactoryMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->idMaskFactoryMock = $this->getMockBuilder(QuoteIdMaskFactory::class)
            ->disableOriginalConstructor()
            ->addMethods(['__wakeup'])
            ->onlyMethods(['create'])
            ->getMock();
        $this->methodManagementMock = $this->createMock(
            ShippingMethodManagementInterface::class
        );
        $this->model = new ShippingMethodManagement(
            $this->methodManagementMock,
            $this->idMaskFactoryMock
        );
    }

    /**
     * @return void
     * @covers \Magento\GiftRegistry\Model\GuestCart\ShippingMethodManagement::estimateByRegistryId
     */
    public function testEstimateByRegistryId(): void
    {
        $cartId = 1;
        $maskedCartId = '8909fa89ced';
        $giftRegistryId = 1;

        $quoteIdMask = $this->getMockBuilder(QuoteIdMask::class)
            ->addMethods(['getQuoteId'])
            ->onlyMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $quoteIdMask->expects($this->any())->method('getQuoteId')->willReturn($cartId);
        $quoteIdMask->expects($this->any())->method('load')->with($maskedCartId, 'masked_id')->willReturnSelf();

        $this->idMaskFactoryMock->expects($this->once())->method('create')->willReturn($quoteIdMask);

        $this->methodManagementMock->expects($this->once())
            ->method('estimateByRegistryId')
            ->with($cartId, $giftRegistryId);

        $this->model->estimateByRegistryId($maskedCartId, $giftRegistryId);
    }
}
