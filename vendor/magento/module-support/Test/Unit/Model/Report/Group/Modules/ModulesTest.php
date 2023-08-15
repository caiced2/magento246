<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Modules;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\ModuleList;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\Website;
use Magento\Support\Model\Report\Group\Modules\Modules;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModulesTest extends TestCase
{
    /**
     * @var FullModuleList|MockObject
     */
    protected $fullModuleListMock;

    /**
     * @var ModuleList|MockObject
     */
    protected $enabledModuleListMock;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $configMock;

    /**
     * @var StoreManager|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Dir|MockObject
     */
    protected $moduleDirMock;

    /**
     * @var Modules
     */
    protected $modules;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fullModuleListMock = $this->createMock(FullModuleList::class);
        $this->enabledModuleListMock = $this->createMock(ModuleList::class);
        $this->configMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManager::class);
        $this->moduleDirMock = $this->createMock(Dir::class);

        $this->modules = $this->objectManagerHelper->getObject(
            Modules::class,
            [
                'fullModuleList'=>  $this->fullModuleListMock,
                'storeManager' =>   $this->storeManagerMock,
                'moduleList' =>     $this->enabledModuleListMock,
                'moduleDir' =>      $this->moduleDirMock,
                'config' =>         $this->configMock
            ]
        );
    }

    /**
     * @param string $moduleName
     * @param bool $expectedResult
     * @return void
     * @dataProvider isModuleEnabledDataProvider
     */
    public function testIsModuleEnabled($moduleName, $expectedResult)
    {
        $modulesName = ['Magento_Backend', 'Magento_Cms'];

        $this->enabledModuleListMock->expects($this->once())
            ->method('getNames')
            ->willReturn($modulesName);

        $this->assertSame($expectedResult, $this->modules->isModuleEnabled($moduleName));
    }

    /**
     * @return array
     */
    public function isModuleEnabledDataProvider()
    {
        return [
            ['moduleName' => 'Magento_Backend', 'expectedResult' => true],
            ['moduleName' => 'Magento_Cms', 'expectedResult' => true],
            ['moduleName' => 'Magento_Catalog', 'expectedResult' => false]
        ];
    }

    /**
     * @return void
     */
    public function testGetFullModulesList()
    {
        $modulesList = [
            'Magento_Backend' => ['setup_version' => '2.0.0'],
            'Magento_Cms' => ['setup_version' => '2.0.0']
        ];
        $expectedResult = [
            'Magento_Backend' => '2.0.0',
            'Magento_Cms' => '2.0.0'
        ];

        $this->fullModuleListMock->expects($this->once())
            ->method('getAll')
            ->willReturn($modulesList);

        $this->assertSame($expectedResult, $this->modules->getFullModulesList());
    }

    /**
     * @param string $moduleName
     * @param string $modulePath
     * @return void
     * @dataProvider getModulePathDataProvider
     */
    public function testGetModulePath($moduleName, $modulePath)
    {
        $this->moduleDirMock->expects($this->once())
            ->method('getDir')
            ->with($moduleName, '')
            ->willReturn($modulePath);

        $this->assertSame($modulePath, $this->modules->getModulePath($moduleName));
    }

    /**
     * @return array
     */
    public function getModulePathDataProvider()
    {
        return [
            [
                'moduleName' => 'Magento_Backend',
                'modulePath' => 'app/code/Magento/Backend/'
            ],
            [
                'moduleName' => 'Vendor_HelloWorld',
                'modulePath' => 'app/code/Vendor/HelloWorld/'
            ]
        ];
    }

    /**
     * @param string $moduleName
     * @param bool $customFlag
     * @return void
     * @dataProvider isCustomModuleDataProvider
     */
    public function testIsCustomModule($moduleName, $customFlag)
    {
        $this->assertSame($customFlag, $this->modules->isCustomModule($moduleName));
    }

    /**
     * @return array
     */
    public function isCustomModuleDataProvider()
    {
        return [
            ['moduleName' => 'Magento_Backend', 'customFlag' => false],
            ['moduleName' => 'Magento_Cms', 'customFlag' => false],
            ['moduleName' => 'Vendor_HelloWorld', 'customFlag' => true],
        ];
    }

    /**
     * @param string $moduleName
     * @param array $websites
     * @param array $isSetFlagReturnValues
     * @param array $expectedResult
     * @return void
     * @dataProvider getOutputFlagInfoDataProvider
     */
    public function testGetOutputFlagInfo(
        $moduleName,
        array $websites,
        array $isSetFlagReturnValues,
        array $expectedResult
    ) {
        $this->configMock->expects($this->any())
            ->method('isSetFlag')
            ->willReturnMap($isSetFlagReturnValues);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);

        $this->assertSame($expectedResult, $this->modules->getOutputFlagInfo($moduleName));
    }

    /**
     * @param string $name
     * @param int $id
     * @param \Magento\Store\Model\Store[]|MockObject[] $stores
     * @return \Magento\Store\Model\Website|MockObject
     */
    protected function getWebsiteMock($name, $id, array $stores = [])
    {
        $websiteMock = $this->getMockBuilder(Website::class)
            ->setMethods(['getName', 'getId', 'getStores'])
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $websiteMock->expects($this->any())
            ->method('getStores')
            ->willReturn($stores);

        return $websiteMock;
    }

    /**
     * @param string $name
     * @param int $id
     * @return \Magento\Store\Model\Store|MockObject
     */
    protected function getStoreMock($name, $id)
    {
        $storeMock = $this->getMockBuilder(Store::class)
            ->setMethods(['getName', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $storeMock;
    }

    /**
     * @return array
     */
    public function getOutputFlagInfoDataProvider()
    {
        $path = 'advanced/modules_disable_output/';
        return [
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, true],
                ],
                'expectedResult' => [
                    '{[Default Config] = Disable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [
                    $this->getWebsiteMock('Default Website', 1),
                    $this->getWebsiteMock('Second Website', 2)
                ],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 1, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 2, true],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}',
                    '{[Default Website] = Enable}',
                    '{[Second Website] = Disable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [
                    $this->getWebsiteMock(
                        'Default Website',
                        1,
                        [
                            $this->getStoreMock('Default Store', 1),
                            $this->getStoreMock('Second Store', 2)
                        ]
                    ),
                ],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 1, true],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_STORES, 1, true],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_STORES, 2, false],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}',
                    '{[Default Website] = Disable}',
                    '{[Default Website] => [Default Store] = Disable}',
                    '{[Default Website] => [Second Store] = Enable}'
                ]
            ],
        ];
    }
}
