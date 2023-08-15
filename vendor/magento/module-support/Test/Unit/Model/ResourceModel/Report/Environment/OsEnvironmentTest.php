<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\Environment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\ResourceModel\Report\Environment\OsEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OsEnvironmentTest extends TestCase
{
    /**
     * @var OsEnvironment
     */
    protected $osEnvironment;

    /**
     * @var PhpInfo|MockObject
     */
    protected $phpInfoMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->phpInfoMock = $this->getMockBuilder(
            PhpInfo::class
        )
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getOsEnvironmentDataProvider
     */
    public function testGetOsEnvironment($phpInfo, $expectedResult)
    {
        $this->phpInfoMock->expects($this->any())
            ->method('getCollectPhpInfo')
            ->willReturn($phpInfo);

        $this->osEnvironment = $this->objectManagerHelper->getObject(
            OsEnvironment::class,
            ['phpInfo' => $this->phpInfoMock]
        );

        $this->assertSame($expectedResult, $this->osEnvironment->getOsInformation());
    }

    /**
     * @return array
     */
    public function getOsEnvironmentDataProvider()
    {
        return [
            [
                'phpInfo' => ['General' => ['System' => 'Test information']],
                'expectedResult' => ['OS Information', 'Test information']
            ],
            [
                'phpInfo' => ['General' => []],
                'expectedResult' => []
            ],
            [
                'phpInfo' => [],
                'expectedResult' => []
            ],
        ];
    }
}
