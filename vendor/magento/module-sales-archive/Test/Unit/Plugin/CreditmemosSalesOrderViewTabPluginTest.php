<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Test\Unit\Plugin;

use Magento\Framework\Authorization;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Block\Adminhtml\Order\View\Tab\Creditmemos;
use Magento\SalesArchive\Plugin\CreditmemosSalesOrderViewTabPlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Order creditmemos plugin tab test
 */
class CreditmemosSalesOrderViewTabPluginTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var CreditmemosSalesOrderViewTabPlugin
     */
    private $creditmemosPlugin;

    /**
     * @var Creditmemos
     */
    private $creditmemosTab;

    /**
     * @var Authorization|MockObject
     */
    private $authorizationMock;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->authorizationMock = $this->createMock(Authorization::class);
        $this->creditmemosTab = $this->createMock(Creditmemos::class);
        $this->creditmemosPlugin = $this->objectManager->getObject(
            CreditmemosSalesOrderViewTabPlugin::class,
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
            ->with('Magento_SalesArchive::creditmemos')
            ->willReturn($isAllowed);

        $this->assertEquals($expectedResult, $this->creditmemosPlugin->afterCanShowTab($this->creditmemosTab, true));
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
