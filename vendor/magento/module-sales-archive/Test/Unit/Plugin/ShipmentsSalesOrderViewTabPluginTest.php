<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Plugin;

use Magento\Framework\Authorization;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Shipments;
use Magento\SalesArchive\Plugin\ShipmentsSalesOrderViewTabPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Order shipments tab test
 */
class ShipmentsSalesOrderViewTabPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Shipments
     */
    private $shipmentsTab;

    /**
     * @var ShipmentsSalesOrderViewTabPlugin
     */
    private $shipmentsPlugin;

    /**
     * @var Authorization|MockObject
     */
    private $authorizationMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->authorizationMock = $this->createMock(Authorization::class);
        $this->shipmentsTab = $this->createMock(Shipments::class);
        $this->shipmentsPlugin = $this->objectManager->getObject(
            ShipmentsSalesOrderViewTabPlugin::class,
            [
                'authorization' => $this->authorizationMock
            ]
        );
    }

    /**
     * @param bool $isVirtual
     * @param bool $isAllowed
     * @param bool $expectedResult
     * @dataProvider canShowTabDataProvider
     */
    public function testAfterCanShowTab($isVirtual, $isAllowed, $expectedResult)
    {
        $this->authorizationMock->expects($this->any())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::shipments')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->shipmentsPlugin->afterCanShowTab($this->shipmentsTab, $isVirtual));
    }

    /**
     * @return array
     */
    public function canShowTabDataProvider()
    {
        return [
            [true, true,true],
            [true, false,false],
            [false, true,false],
            [false, false,false]
        ];
    }
}
