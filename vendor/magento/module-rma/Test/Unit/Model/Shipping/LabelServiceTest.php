<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Model\Shipping;

use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Helper\Data;
use Magento\Rma\Model\Rma;
use Magento\Rma\Model\Shipping;
use Magento\Rma\Model\Shipping\LabelService;
use Magento\Rma\Model\ShippingFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LabelServiceTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    /**
     * @var LabelService
     */
    private $labelServiceModel;

    /**
     * @var Data|MockObject
     */
    private $rmaHelper;

    /**
     * @var ShippingFactory|MockObject
     */
    private $shippingFactory;

    /**
     * @var \Magento\Rma\Model\ResourceModel\ShippingFactory|MockObject
     */
    private $shippingResourceFactory;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystem;

    /**
     * @var Json|MockObject
     */
    private $json;

    protected function setUp(): void
    {
        $this->rmaHelper = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingFactory = $this->getMockBuilder(ShippingFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingResourceFactory = $this->getMockBuilder(\Magento\Rma\Model\ResourceModel\ShippingFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->labelServiceModel = $this->objectManagerHelper->getObject(
            LabelService::class,
            [
                'rmaHelper' => $this->rmaHelper,
                'shippingFactory' => $this->shippingFactory,
                'shippingResourceFactory' => $this->shippingResourceFactory,
                'filesystem' => $this->filesystem,
                'json' => $this->json
            ]
        );
    }

    public function testCreateShippingLabel()
    {
        $packages = [
            [
                "params" => [
                    "weight" => 10,
                    "height" => 10,
                    "width" => 10
                ]
            ],
            [
                "params" => [
                    "weight" => 20,
                    "height" => 20,
                    "width" => 20
                ]
            ]
        ];

        $data = [
            "carrier_title" => "SuperCarrier",
            "method_title" => "SuperMethod",
            "price" => 100,
            "code" => "EE_RR_OO",
            "packages" => $packages
        ];

        $rmaModel = $this->getMockBuilder(Rma::class)
            ->disableOriginalConstructor()
            ->getMock();

        $abstractCarrier = $this->getMockBuilder(AbstractCarrierOnline::class)
            ->disableOriginalConstructor()
            ->getMock();
        $abstractCarrier->expects($this->any())
            ->method('isShippingLabelsAvailable')
            ->willReturn(true);
        $this->rmaHelper->expects($this->any())
            ->method('getCarrier')
            ->willReturn($abstractCarrier);

        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shipping->expects($this->any())
            ->method('getShippingLabelByRma')
            ->willReturnSelf();
        $this->shippingFactory->expects($this->any())
            ->method('create')
            ->willReturn($shipping);
        $response = new DataObject(['info'=> ['data']]);
        $shipping->expects($this->any())
            ->method('requestToShipment')
            ->willReturn($response);

        $this->json->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->assertTrue($this->labelServiceModel->createShippingLabel($rmaModel, $data));
    }
}
