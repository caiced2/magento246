<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Shipping;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\RmaFactory;
use Magento\Rma\Model\Shipping;
use Magento\Rma\Model\Shipping\Info;
use Magento\Rma\Model\ShippingFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InfoTest extends TestCase
{
    /**
     * @var Info
     */
    private $model;

    /**
     * @var Data|MockObject
     */
    private $rmaDataMock;

    /**
     * @var RmaFactory|MockObject
     */
    private $rmaFactoryMock;

    /**
     * @var ShippingFactory|MockObject
     */
    private $shippingFactoryMock;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManager($this);
        $this->rmaDataMock = $this->createMock(Data::class);
        $this->rmaFactoryMock = $this->getMockBuilder('Magento\Rma\Model\RmaFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->shippingFactoryMock = $this->getMockBuilder('Magento\Rma\Model\ShippingFactory')
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            Info::class,
            [
                'rmaData' => $this->rmaDataMock,
                'rmaFactory' => $this->rmaFactoryMock,
                'shippingFactory' => $this->shippingFactoryMock
            ]
        );
    }

    public function testGetTrackingInfoByRmaWithVulnerableHash()
    {
        $rmaId = '123';
        $protectedCode = '0e015339760548602306096794382326';
        $maliciousProtectedCode = '0';
        $this->model->setRmaId($rmaId);
        $this->model->setProtectCode($maliciousProtectedCode);
        $rmaMock = $this->getMockBuilder(Rma::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getEntityId', 'getProtectCode'])
            ->getMock();
        $this->rmaFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rmaMock);
        $rmaMock->expects($this->once())
            ->method('load')
            ->with($rmaId)
            ->willReturnSelf();
        $rmaMock->expects($this->once())
            ->method('getEntityId')
            ->willReturn($rmaId);
        $rmaMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectedCode);
        $this->assertEmpty($this->model->getTrackingInfoByRma());
        $this->assertEmpty($this->model->getTrackingInfo());
    }

    public function testGetTrackingInfoByTrackIdWithVulnerableHash()
    {
        $trackId = '123';
        $protectedCode = '0e015339760548602306096794382326';
        $maliciousProtectedCode = '0';
        $this->model->setTrackId($trackId);
        $this->model->setProtectCode($maliciousProtectedCode);
        $rmaShippingMock = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getId', 'getProtectCode', 'getNumberDetail'])
            ->getMock();
        $this->shippingFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rmaShippingMock);
        $rmaShippingMock->expects($this->once())
            ->method('load')
            ->with($trackId)
            ->willReturnSelf();
        $rmaShippingMock->expects($this->once())
            ->method('getId')
            ->willReturn($trackId);
        $rmaShippingMock->expects($this->once())
            ->method('getProtectCode')
            ->willReturn($protectedCode);
        $this->assertEmpty($this->model->getTrackingInfoByTrackId());
        $this->assertEmpty($this->model->getTrackingInfo());
    }
}
