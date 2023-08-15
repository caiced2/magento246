<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Tab\General;

use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod;
use Magento\Rma\Model\Item as RmaItem;
use Magento\Rma\Model\Shipping;
use Magento\Rma\Model\ShippingFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod
 */
class ShippingmethodTest extends TestCase
{
    /**
     * @var Shippingmethod
     */
    protected $shippingMethod;

    /**
     * @var Registry|MockObject
     */
    protected $registry;

    /**
     * @var ShippingFactory|MockObject
     */
    protected $shippingFactory;

    /**
     * @var Shipping|MockObject
     */
    private $shipping;

    /**
     * @var RmaItem|MockObject
     */
    private $rma;

    /**
     * @var Json|MockObject
     */
    private $json;

    protected function setUp(): void
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingFactory = $this->getMockBuilder(ShippingFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->json = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new ObjectManager($this);
        $this->shippingMethod = $objectManager->getObject(
            Shippingmethod::class,
            [
                'shippingFactory' => $this->shippingFactory,
                'registry' => $this->registry,
                'json' => $this->json
            ]
        );
    }

    private function getShipment()
    {
        $this->shipping = $this->getMockBuilder(Shipping::class)
            ->setMethods(['getShippingLabelByRma', 'getPackages', 'getCarrierCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rma = $this->getMockBuilder(RmaItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->shipping);
        $this->shipping->expects($this->once())
            ->method('getShippingLabelByRma')
            ->with($this->rma)
            ->willReturnSelf();
        $this->registry->expects($this->once())
            ->method('registry')
            ->with('current_rma')
            ->willReturn($this->rma);
    }

    /**
     * @covers \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::getPackages
     * @param array $packages
     * @dataProvider packageProvider
     */
    public function testGetPackages($packages)
    {
        $this->getShipment();

        $this->shipping->expects($this->once())
            ->method('getPackages')
            ->willReturn(json_encode($packages));

        $this->json->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->assertEquals($packages, $this->shippingMethod->getPackages($packages));
    }

    /**
     * @return array
     */
    public function packageProvider()
    {
        return [
            [[]],
            [['test']],
            [['package' => ['test']]]
        ];
    }

    /**
     * @covers \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\Shippingmethod::canDisplayCustomValue
     * @param string|null $carrier
     * @param bool $result
     * @dataProvider carrierDataProvider
     */
    public function testCanDisplayCustomValue($carrier, $result)
    {
        $this->getShipment();

        $this->shipping->expects($this->once())
            ->method('getCarrierCode')
            ->willReturn($carrier);

        $this->assertEquals($result, $this->shippingMethod->canDisplayCustomValue());
    }

    /**
     * @return array
     */
    public function carrierDataProvider()
    {
        return [
            ['carrier' => null, 'result' => false],
            ['carrier' => 'usps', 'result' => false],
            ['carrier' => 'dhl', 'result' => true],
            ['carrier' => 'fedex', 'result' => true],
        ];
    }
}
