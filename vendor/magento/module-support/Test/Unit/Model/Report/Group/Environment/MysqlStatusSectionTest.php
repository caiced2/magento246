<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Environment;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Module\ModuleResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Environment\MysqlStatusSection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MysqlStatusSectionTest extends TestCase
{
    /**
     * @var MysqlStatusSection
     */
    protected $mysqlStatusReport;

    /**
     * @var ModuleResource|MockObject
     */
    protected $resourceMock;

    /**
     * @var AdapterInterface|MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->resourceConnectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resourceMock = $this->createMock(ModuleResource::class);
        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->resourceConnectionMock);
        $this->mysqlStatusReport = $this->objectManagerHelper->getObject(
            MysqlStatusSection::class,
            ['resource' => $this->resourceMock]
        );
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $variables = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '0',
            'Slave_running' => 'OFF',
            'Com_select' => '0',
            'Test_info' => '0'
        ];
        $variablesAfter10Sec = [
            'Aborted_clients' => '0',
            'Aborted_connects' => '1',
            'Slave_running' => 'OFF',
            'Test_info' => '1'
        ];
        $expectedResult = [
            MysqlStatusSection::REPORT_TITLE => [
                'headers' => ['Variable', 'Value', 'Value after 10 sec'],
                'data' => [
                    ['Aborted_clients', '0', '0'],
                    ['Aborted_connects', '0', '1 (diff: +1)'],
                    ['Slave_running', 'OFF', 'OFF'],
                    ['Com_select', '0', 'n/a']
                ]
            ]
        ];

        $this->resourceConnectionMock
            ->method('fetchPairs')
            ->withConsecutive(['SHOW GLOBAL STATUS'], ['SHOW GLOBAL STATUS'])
            ->willReturnOnConsecutiveCalls($variables, $variablesAfter10Sec);
        $this->assertSame($expectedResult, $this->mysqlStatusReport->generate());
    }
}
