<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Plugin;

use Magento\Framework\Authorization;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Invoices;
use Magento\SalesArchive\Plugin\InvoicesSalesOrderViewTabPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Order invoices tab test
 */
class InvoicesSalesOrderViewTabPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Invoices
     */
    private $invoicesTab;

    /**
     * @var InvoicesSalesOrderViewTabPlugin
     */
    private $invoicesPlugin;

    /**
     * @var Authorization|MockObject
     */
    private $authorizationMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->authorizationMock = $this->createMock(Authorization::class);
        $this->invoicesTab = $this->createMock(Invoices::class);
        $this->invoicesPlugin = $this->objectManager->getObject(
            InvoicesSalesOrderViewTabPlugin::class,
            [
                'authorization' => $this->authorizationMock
            ]
        );
    }

    /**
     * @param bool $isAllowed
     * @param bool $expectedResult
     * @dataProvider canShowDataProvider
     */
    public function testAfterCanShowTab($isAllowed, $expectedResult)
    {
        $this->authorizationMock->expects($this->any())
            ->method('isAllowed')
            ->with('Magento_SalesArchive::invoices')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->invoicesPlugin->afterCanShowTab($this->invoicesTab, true));
    }

    /**
     * @return array
     */
    public function canShowDataProvider()
    {
        return [
            [true, true],
            [false, false]
        ];
    }
}
