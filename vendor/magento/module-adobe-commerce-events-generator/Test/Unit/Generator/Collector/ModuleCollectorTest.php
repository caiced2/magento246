<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsGenerator\Test\Unit\Generator\Collector;

use Magento\AdobeCommerceEventsGenerator\Generator\Collector\ModuleCollector;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for ModuleCollector class.
 */
class ModuleCollectorTest extends TestCase
{
    /**
     * @var File|MockObject
     */
    private $fileMock;

    /**
     * @var ModuleCollector
     */
    private ModuleCollector $moduleCollector;

    protected function setUp(): void
    {
        $this->fileMock = $this->createMock(File::class);
        $this->moduleCollector = new ModuleCollector($this->fileMock);
    }

    /**
     * Checks that a module's information is correctly collected when composer.json and module.xml files for the module
     * containing the input class are found.
     *
     * @return void
     */
    public function testCollect()
    {
        $testDir = '/testDir';
        $composerPath = $testDir . '/composer.json';
        $moduleXmlPath = $testDir . '/etc/module.xml';
        $testClass = $testDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::once())
            ->method('getParentDirectory')
            ->with($testClass)
            ->willReturn($testDir);
        $this->fileMock->expects(self::exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$composerPath],
                [$moduleXmlPath]
            )
            ->willReturn(true);
        $this->fileMock->expects(self::exactly(2))
            ->method('fileGetContents')
            ->withConsecutive(
                [$composerPath],
                [$moduleXmlPath]
            )
            ->willReturnOnConsecutiveCalls(
                '{"name": "magento/module-test", "type": "magento2-module"}',
                '<?xml version="1.0"?><config><module name="Magento_Test" /></config>'
            );

        $this->moduleCollector->collect($reflectionClassMock);
        $moduleData = $this->moduleCollector->getModules();
        $this->assertEquals(
            [
                'magento/module-test' =>
                    [
                        'packageName' => 'magento/module-test',
                        'name' => 'Magento_Test'
                    ]
            ],
            $moduleData
        );
    }

    /**
     * Checks that a module's information is correctly collected when a composer.json file but not a module.xml file for
     * the module containing the input class are found.
     *
     * @return void
     */
    public function testCollectModuleXmlNotFound()
    {
        $testDir = '/testDir';
        $composerPath = $testDir . '/composer.json';
        $moduleXmlPath = $testDir . '/etc/module.xml';
        $testClass = $testDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::once())
            ->method('getParentDirectory')
            ->with($testClass)
            ->willReturn($testDir);
        $this->fileMock->expects(self::exactly(2))
            ->method('isExists')
            ->withConsecutive(
                [$composerPath],
                [$moduleXmlPath]
            )
            ->willReturn(true, false);
        $this->fileMock->expects(self::once())
            ->method('fileGetContents')
            ->with($composerPath)
            ->willReturn(
                '{"name": "magento/module-test", "type": "magento2-module"}'
            );

        $this->moduleCollector->collect($reflectionClassMock);
        $moduleData = $this->moduleCollector->getModules();
        $this->assertEquals(
            [
                'magento/module-test' =>
                    [
                        'packageName' => 'magento/module-test'
                    ]
            ],
            $moduleData
        );
    }

    /**
     * Checks that an attempt to collect a module's information correctly terminates when a composer.json file is not
     * found in the directories contained in the input class' path.
     *
     * @return void
     */
    public function testCollectComposerJsonNotFound()
    {
        $testDir = '/testDir';
        $testDirComposer = $testDir . '/composer.json';
        $subDir = $testDir . '/subDir';
        $subDirComposer = $subDir . '/composer.json';
        $testClass = $subDir . '/TestClass.php';

        $reflectionClassMock = $this->createMock(ReflectionClass::class);
        $reflectionClassMock->expects(self::once())
            ->method('getFileName')
            ->willReturn($testClass);

        $this->fileMock->expects(self::exactly(3))
            ->method('getParentDirectory')
            ->withConsecutive(
                [$testClass],
                [$subDir],
                [$testDir]
            )
            ->willReturnOnConsecutiveCalls(
                $subDir,
                $testDir,
                '/'
            );
        $this->fileMock->expects(self::exactly(3))
            ->method('isExists')
            ->withConsecutive(
                [$subDirComposer],
                [$subDirComposer],
                [$testDirComposer]
            )
            ->willReturn(
                $this->throwException(new FileSystemException(__('Some exception'))),
                false,
                false
            );

        $this->moduleCollector->collect($reflectionClassMock);
        $this->assertEquals(
            [],
            $this->moduleCollector->getModules()
        );
    }
}
