<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\OfflineShipping\Model\Carrier\Flatrate;
use Magento\Rma\Model\RmaFactory;
use Magento\Rma\Model\Shipping;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\OrderFactory;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Shipping\Model\Shipment\ReturnShipmentFactory;
use PHPUnit\Framework\TestCase;

class ShippingTest extends TestCase
{
    /**
     * @var Shipping
     */
    protected $model;

    /**
     * @var CarrierFactory
     */
    protected $carrierFactory;

    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManager($this);

        $orderFactory = $this->createPartialMock(OrderFactory::class, ['create']);
        $regionFactory = $this->createPartialMock(RegionFactory::class, ['create']);
        $this->carrierFactory = $this->createMock(CarrierFactory::class);
        $returnFactory = $this->createPartialMock(ReturnShipmentFactory::class, ['create']);
        $rmaFactory = $this->createPartialMock(RmaFactory::class, ['create']);
        $filesystem = $this->createMock(Filesystem::class);

        $this->model = $objectManagerHelper->getObject(
            Shipping::class,
            [
                'orderFactory' => $orderFactory,
                'regionFactory' => $regionFactory,
                'returnFactory' => $returnFactory,
                'carrierFactory' => $this->carrierFactory,
                'rmaFactory' => $rmaFactory,
                'filesystem' => $filesystem
            ]
        );
    }

    /**
     * @dataProvider isCustomDataProvider
     * @param bool $expectedResult
     * @param string $carrierCodeToSet
     */
    public function testIsCustom($expectedResult, $carrierCodeToSet)
    {
        $this->model->setCarrierCode($carrierCodeToSet);
        $this->assertEquals($expectedResult, $this->model->isCustom());
    }

    /**
     * @return array
     */
    public static function isCustomDataProvider()
    {
        return [
            [true, Track::CUSTOM_CARRIER_CODE],
            [false, 'not-custom']
        ];
    }

    public function testGetNumberDetailWithoutCarrierInstance()
    {
        $carrierTitle = 'Carrier Title';
        $trackNumber = 'US1111CA';
        $expected = [
            'title' => $carrierTitle,
            'number' => $trackNumber,
        ];
        $this->model->setCarrierTitle($carrierTitle);
        $this->model->setTrackNumber($trackNumber);

        $this->assertEquals($expected, $this->model->getNumberDetail());
    }

    /**
     * @dataProvider getNumberDetailDataProvider
     */
    public function testGetNumberDetail($trackingInfo, $trackNumber, $expected)
    {
        $carrierMock = $this->getMockBuilder(Flatrate::class)
            ->addMethods(['getTrackingInfo', 'setStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->carrierFactory->expects($this->once())
            ->method('create')
            ->willReturn($carrierMock);
        $carrierMock->expects($this->any())
            ->method('getTrackingInfo')
            ->willReturn($trackingInfo);

        $this->model->setTrackNumber($trackNumber);
        $this->assertEquals($expected, $this->model->getNumberDetail());
    }

    public function getNumberDetailDataProvider()
    {
        $trackNumber = 'US1111CA';
        return [
            'With tracking info' => ['some tracking info', $trackNumber, 'some tracking info'],
            'Without tracking info' => [false, $trackNumber, __('No detail for number "' . $trackNumber . '"')]
        ];
    }
}
