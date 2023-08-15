<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\Environment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\ResourceModel\Report\Environment\ApacheEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApacheEnvironmentTest extends TestCase
{
    /**
     * @var ApacheEnvironment
     */
    protected $apacheEnvironment;

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
     * @return void
     */
    protected function setGeneralTestObj($phpInfo)
    {
        $this->phpInfoMock->expects($this->any())
            ->method('getCollectPhpInfo')
            ->willReturn($phpInfo);
        $this->apacheEnvironment = $this->objectManagerHelper->getObject(
            ApacheEnvironment::class,
            ['phpInfo' => $this->phpInfoMock]
        );
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getVersionDataProvider
     */
    public function testGetVersion($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->apacheEnvironment->getVersion());
    }

    /**
     * @return array
     */
    public function getVersionDataProvider()
    {
        return [
            [
                'phpInfo' => [
                    'apache2handler' => ['Apache Version' => 'Apache 2.4']
                ],
                'expectedResult' => ['Apache Version', 'Apache 2.4']
            ],
            [
                'phpInfo' => ['apache2handler' => []],
                'expectedResult' => []
            ],
            [
                'phpInfo' => [],
                'expectedResult' => []
            ]
        ];
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getServerAddressDataProvider
     */
    public function testGetServerAddress($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->apacheEnvironment->getServerAddress());
    }

    /**
     * @return array
     */
    public function getServerAddressDataProvider()
    {
        return [
            [
                'phpInfo' => [
                    'Apache Environment' => [
                        'SERVER_ADDR' => '127.0.0.1',
                        'SERVER_PORT' => '80'
                    ]
                ],
                'expectedResult' => ['Server Address', '127.0.0.1:80']
            ],
            [
                'phpInfo' => [
                    'PHP Variables' => [
                        '_SERVER["SERVER_ADDR"]' => '127.0.0.2',
                        '_SERVER["SERVER_PORT"]' => '81'
                    ]
                ],
                'expectedResult' => ['Server Address', '127.0.0.2:81']
            ],
            [
                'phpInfo' => ['Apache Environment' => ['SERVER_ADDR' => '127.0.0.1']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['Apache Environment' => ['SERVER_PORT' => '80']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['PHP Variables' => ['_SERVER["SERVER_ADDR"]' => '127.0.0.1']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['PHP Variables' => ['_SERVER["SERVER_PORT"]' => '80']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => [],
                'expectedResult' => []
            ],
        ];
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getRemoteAddressDataProvider
     */
    public function testGetRemoteAddress($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->apacheEnvironment->getRemoteAddress());
    }

    /**
     * @return array
     */
    public function getRemoteAddressDataProvider()
    {
        return [
            [
                'phpInfo' => [
                    'Apache Environment' => [
                        'REMOTE_ADDR' => '127.0.0.1',
                        'REMOTE_PORT' => '80'
                    ]
                ],
                'expectedResult' => ['Remote Address', '127.0.0.1:80']
            ],
            [
                'phpInfo' => [
                    'PHP Variables' => [
                        '_SERVER["REMOTE_ADDR"]' => '127.0.0.2',
                        '_SERVER["REMOTE_PORT"]' => '81'
                    ]
                ],
                'expectedResult' => ['Remote Address', '127.0.0.2:81']
            ],
            [
                'phpInfo' => ['Apache Environment' => ['REMOTE_ADDR' => '127.0.0.1']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['Apache Environment' => ['REMOTE_PORT' => '80']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['PHP Variables' => ['_SERVER["REMOTE_ADDR"]' => '127.0.0.1']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => ['PHP Variables' => ['_SERVER["REMOTE_PORT"]' => '80']],
                'expectedResult' => []
            ],
            [
                'phpInfo' => [],
                'expectedResult' => []
            ],
        ];
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getLoadedModulesDataProvider
     */
    public function testGetLoadedModules($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->apacheEnvironment->getLoadedModules());
    }

    /**
     * @return array
     */
    public function getLoadedModulesDataProvider()
    {
        return [
            [
                'phpInfo' => [
                    'apache2handler' => ['Loaded Modules' => 'core mod_win32']
                ],
                'expectedResult' => ['Apache Loaded Modules', 'core' . "\n" . 'mod_win32']
            ],
            [
                'phpInfo' => ['apache2handler' => []],
                'expectedResult' => []
            ],
            [
                'phpInfo' => [],
                'expectedResult' => []
            ]
        ];
    }
}
