<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View;

use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Report\View\Tabs;
use Magento\Support\Model\Report;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TabsTest extends TestCase
{
    /**
     * @var Tabs
     */
    protected $reportTabsBlock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var Registry|MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var Report|MockObject
     */
    protected $reportMock;

    protected function setUp(): void
    {
        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->reportMock = $this->getMockBuilder(Report::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->reportTabsBlock = $this->objectManagerHelper->getObject(
            Tabs::class,
            [
                'coreRegistry' => $this->coreRegistryMock
            ]
        );
    }

    public function testGetReportDataIsSet()
    {
        $this->reportTabsBlock->setData('report', $this->reportMock);

        $this->coreRegistryMock->expects($this->never())
            ->method('registry');

        $this->assertSame($this->reportMock, $this->reportTabsBlock->getReport());
    }

    public function testGetReport()
    {
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_report')
            ->willReturn($this->reportMock);

        $this->assertSame($this->reportMock, $this->reportTabsBlock->getReport());
    }
}
