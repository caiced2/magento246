<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\ResourceModel\Report\Environment;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpEnvironment;
use Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PhpEnvironmentTest extends TestCase
{
    /**
     * @var PhpEnvironment
     */
    protected $phpEnvironment;

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
        $this->phpEnvironment = $this->objectManagerHelper->getObject(
            PhpEnvironment::class,
            ['phpInfo' => $this->phpInfoMock]
        );
    }

    /**
     * @return void
     */
    public function testGetPhpVersion()
    {
        $this->setGeneralTestObj([]);
        $this->assertSame(['PHP Version', PHP_VERSION], $this->phpEnvironment->getVersion());
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getLoadedConfFileDataProvider
     */
    public function testGetLoadedConfFile($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->phpEnvironment->getLoadedConfFile());
    }

    /**
     * @return array
     */
    public function getLoadedConfFileDataProvider()
    {
        return [
            [
                'phpInfo' => ['General' => ['Loaded Configuration File' => 'php.ini']],
                'expectedResult' => ['PHP Loaded Config File', 'php.ini']
            ],
            [
                'phpInfo' => ['General' => []],
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
     * @dataProvider getAdditionalIniFileDataProvider
     */
    public function testGetAdditionalIniFile($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->phpEnvironment->getAdditionalIniFile());
    }

    /**
     * @return array
     */
    public function getAdditionalIniFileDataProvider()
    {
        return [
            [
                'phpInfo' => [],
                'expectedResult' => [],
            ],
            [
                'phpInfo' => ['General' => []],
                'expectedResult' => [],
            ],
            [
                'phpInfo' => ['General' => ['Additional .ini files parsed' => 'some.ini']],
                'expectedResult' => ['PHP Additional .ini files parsed', 'some.ini'],
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetImportantConfigSettingsEmpty()
    {
        $this->setGeneralTestObj([]);
        $this->phpInfoMock->expects($this->any())
            ->method('iniGetAll')
            ->willReturn([]);
        $this->assertSame([], $this->phpEnvironment->getImportantConfigSettings());
    }

    /**
     * @param array $phpInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getImportantConfigSettingsPhpInfoDataProvider
     */
    public function testGetImportantConfigSettingsPhpInfo($phpInfo, $expectedResult)
    {
        $this->setGeneralTestObj($phpInfo);
        $this->assertSame($expectedResult, $this->phpEnvironment->getImportantConfigSettings());
    }

    /**
     * @return array
     */
    public function getImportantConfigSettingsPhpInfoDataProvider()
    {
        return [
            [
                'phpInfo' => [
                    'Core' => [
                        'allow_url_fopen' => ['local' => 'On', 'master' => 'On'],
                        'test_settings' => ['local' => 'On', 'master' => 'On'],
                    ],
                    'PHP Core' => [
                        'log_errors' => ['local' => 'On', 'master' => 'On']
                    ]
                ],
                'expectedResult' => ['PHP Configuration', 'allow_url_fopen => Local = "On", Master = "On"']
            ],
            [
                'phpInfo' => [
                    'PHP Core' => [
                        'allow_url_fopen' => ['local' => 'On', 'master' => 'On']
                    ]
                ],
                'expectedResult' => ['PHP Configuration', 'allow_url_fopen => Local = "On", Master = "On"']
            ],
        ];
    }

    /**
     * @param array $iniInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider getImportantConfigSettingsIniInfoDataProvider
     */
    public function testGetImportantConfigSettingsIniInfo($iniInfo, $expectedResult)
    {
        $this->setGeneralTestObj([]);
        $this->phpInfoMock->expects($this->any())
            ->method('iniGetAll')
            ->willReturn($iniInfo);
        $this->assertSame($expectedResult, $this->phpEnvironment->getImportantConfigSettings());
    }

    /**
     * @return array
     */
    public function getImportantConfigSettingsIniInfoDataProvider()
    {
        return [
            [
                'iniInfo' => [
                    'allow_url_fopen' => ['local_value' => 'On', 'global_value' => 'On'],
                    'test_settings' => ['local_value' => 'On', 'global_value' => 'On']
                ],
                'expectedResult' => ['PHP Configuration', 'allow_url_fopen => Local = "On", Master = "On"']
            ],
            [
                'iniInfo' => [
                    'allow_url_fopen' => ['local_value' => 'On', 'global_value' => 'On'],
                    'log_errors' => ['local_value' => 'On', 'global_value' => 'On']
                ],
                'expectedResult' => [
                    'PHP Configuration', 'allow_url_fopen => Local = "On", Master = "On"' . "\n" .
                    'log_errors => Local = "On", Master = "On"'
                ]
            ]
        ];
    }

    /**
     * @return void
     */
    public function testGetLoadedModulesEmpty()
    {
        $this->setGeneralTestObj([]);
        $this->phpInfoMock->expects($this->any())
            ->method('getLoadedExtensions')
            ->willReturn([]);
        $this->assertSame(['PHP Loaded Modules', 'n/a'], $this->phpEnvironment->getLoadedModules());
    }

    /**
     * @return void
     */
    public function testGetLoadedModulesWithoutPhpInfo()
    {
        $modulesList = [
            'bcmath', 'bz2', 'dom'
        ];
        $expectedResult = [
            'PHP Loaded Modules',
            'bcmath' . "\n" . 'bz2' . "\n" . 'dom [2.9.2]'
        ];
        $modulesVersion = [
            ['bcmath', ''],
            ['bz2', ''],
            ['dom', '2.9.2']
        ];

        $this->setGeneralTestObj([]);
        $this->phpInfoMock->expects($this->any())
            ->method('getLoadedExtensions')
            ->willReturn($modulesList);
        $this->phpInfoMock->expects($this->any())
            ->method('getModuleVersion')
            ->willReturnMap($modulesVersion);
        $this->assertSame($expectedResult, $this->phpEnvironment->getLoadedModules());
    }

    /**
     * @param array $modulesList
     * @param array $modulesVersion
     * @param array $expectedResult
     * @return void
     * @dataProvider getLoadedModulesPhpInfoDataProvider
     */
    public function testGetLoadedModulesPhpInfo($modulesList, $modulesVersion, $expectedResult)
    {
        $this->setGeneralTestObj($modulesList);
        $this->phpInfoMock->expects($this->any())
            ->method('getModuleVersion')
            ->willReturnMap($modulesVersion);
        $this->assertSame($expectedResult, $this->phpEnvironment->getLoadedModules());
    }

    /**
     * @return array
     */
    public function getLoadedModulesPhpInfoDataProvider()
    {
        return [
            [
                'modulesList' => [
                    'curl' => ['cURL Information' => '7.40.0'],
                    'dom' => ['libxml Version' => '2.9.2'],
                    'gd' => ['GD Version' => '2.1.0'],
                    'iconv' => ['iconv library version' => '1.14'],
                    'mcrypt' => ['Version' => '2.5.8'],
                    'pdo_mysql' => ['Client API version' => '5.0.11'],
                    'SimpleXML' => ['Revision' => '$Id: e0de6ee7ef8280a12d77d76f1f971a944cbc8090 $'],
                ],
                'modulesVersion' => [],
                'expectedResult' => [
                    'PHP Loaded Modules',
                    'curl [7.40.0]' . "\n" . 'dom [2.9.2]' . "\n" . 'gd [2.1.0]' . "\n" .
                    'iconv [1.14]' . "\n" . 'mcrypt [2.5.8]' . "\n" . 'pdo_mysql [5.0.11]' . "\n" .
                    'SimpleXML [$Id: e0de6ee7ef8280a12d77d76f1f971a944cbc8090 $]'
                ]
            ],
            [
                'modulesList' => [
                    'curl' => [],
                    'dom' => [],
                    'gd' => [],
                    'iconv' => [],
                    'mcrypt' => [],
                    'pdo_mysql' => [],
                    'SimpleXML' => [],
                ],
                'modulesVersion' => [],
                'expectedResult' => [
                    'PHP Loaded Modules',
                    'curl' . "\n" . 'dom' . "\n" . 'gd' . "\n" . 'iconv' . "\n" .
                    'mcrypt' . "\n" . 'pdo_mysql' . "\n" . 'SimpleXML'
                ]
            ],
            [
                'modulesList' => [
                    'pdo_mysql' => ['PDO Driver for MySQL, client library version' => '5.0.11'],
                ],
                'modulesVersion' => [],
                'expectedResult' => [
                    'PHP Loaded Modules',
                    'pdo_mysql [5.0.11]'
                ]
            ],
            [
                'modulesList' => [
                    'xdebug' => ['some information' => '']
                ],
                'modulesVersion' => [
                    ['xdebug', '2.2.5']
                ],
                'expectedResult' => [
                    'PHP Loaded Modules',
                    'xdebug [2.2.5]'
                ]
            ],
        ];
    }
}
