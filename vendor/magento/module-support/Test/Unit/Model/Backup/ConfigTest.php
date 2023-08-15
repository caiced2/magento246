<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Backup;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup\Config;
use Magento\Support\Model\Backup\Item\Code;
use Magento\Support\Model\Backup\Item\Db;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $objectManagerMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scopeConfigMock = $this->getMockForAbstractClass(ScopeConfigInterface::class);
        $this->objectManagerMock = $this->getMockForAbstractClass(ObjectManagerInterface::class);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            ['scopeConfig' => $this->scopeConfigMock]
        );
        $this->config = $this->objectManagerHelper->getObject(
            Config::class,
            [
                'context' => $this->context,
                'objectManager' => $this->objectManagerMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetBackupItems()
    {
        /** @var Db|MockObject $item */
        $itemDb = $this->createPartialMock(Db::class, ['setData']);
        $itemDb->expects($this->once())
            ->method('setData')
            ->with(['test' => 'test']);

        /** @var Code|MockObject $item */
        $itemCode = $this->createPartialMock(Code::class, ['setData']);
        $itemCode->expects($this->once())
            ->method('setData')
            ->with(['test2' => 'test2']);

        $configItems = [
            'db' => ['class' => 'Db', 'params' => ['test' => 'test']],
            'code' => ['class' => 'Code', 'params' => ['test2' => 'test2']],
        ];
        $expectedResult = [
            'db' => $itemDb,
            'code' => $itemCode
        ];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_BACKUP_ITEMS)
            ->willReturn($configItems);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                ['Code', [], $itemCode],
                ['Db', [], $itemDb],
            ]);

        $this->assertSame($expectedResult, $this->config->getBackupItems());
    }

    /**
     * @param string $type
     * @param string $fileExtension
     * @return void
     * @dataProvider getBackupFileExtensionDataProvider
     */
    public function testGetBackupFileExtension($type, $fileExtension)
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [
                    Config::XML_BACKUP_ITEMS . '/code/params/output_file_extension',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    'tar.gz'
                ],
                [
                    Config::XML_BACKUP_ITEMS . '/db/params/output_file_extension',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    'sql.gz'
                ]
            ]);

        $this->assertSame($fileExtension, $this->config->getBackupFileExtension($type));
    }

    /**
     * @return array
     */
    public function getBackupFileExtensionDataProvider()
    {
        return [
            ['type' => 'db', 'fileExtension' => 'sql.gz'],
            ['type' => 'code', 'fileExtension' => 'tar.gz'],
        ];
    }
}
