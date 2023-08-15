<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Report\Group\Modules;

use Magento\Framework\Module\ModuleResource;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Report\Group\Modules\AbstractModuleSection;
use Magento\Support\Model\Report\Group\Modules\Modules;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractModulesSectionTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Modules|MockObject
     */
    protected $modulesMock;

    /**
     * @var ModuleResource|MockObject
     */
    protected $resourceMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->modulesMock = $this->getMockBuilder(Modules::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->createMock(ModuleResource::class);
    }

    /**
     * @param string $className
     * @param array $dbVersions
     * @param array $enabledModules
     * @param array $allModules
     * @param array $modulesInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        $className,
        array $dbVersions,
        array $enabledModules,
        array $allModules,
        array $modulesInfo,
        array $expectedResult
    ) {
        /** @var AbstractModuleSection $section */
        $section = $this->objectManagerHelper->getObject(
            $className,
            [
                'modules' => $this->modulesMock,
                'resource' => $this->resourceMock
            ]
        );

        $this->resourceMock->expects($this->any())
            ->method('getDbVersion')
            ->willReturnMap($dbVersions['schemaVersions']);
        $this->resourceMock->expects($this->any())
            ->method('getDataVersion')
            ->willReturnMap($dbVersions['dataVersions']);

        $this->modulesMock->expects($this->once())
            ->method('getFullModulesList')
            ->willReturn($allModules);
        $this->modulesMock->expects($this->any())
            ->method('getModulePath')
            ->willReturnMap($modulesInfo['modulePathMap']);
        $this->modulesMock->expects($this->any())
            ->method('isCustomModule')
            ->willReturnMap($modulesInfo['customModuleMap']);
        $this->modulesMock->expects($this->any())
            ->method('getOutputFlagInfo')
            ->willReturnMap($modulesInfo['outputFlagInfoMap']);
        $this->modulesMock->expects($this->any())
            ->method('isModuleEnabled')
            ->willReturnMap($enabledModules);

        $this->assertSame($expectedResult, $section->generate());
    }

    /**
     * @return array
     */
    abstract public function generateDataProvider();
}
