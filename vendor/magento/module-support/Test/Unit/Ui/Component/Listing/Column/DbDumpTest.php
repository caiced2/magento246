<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Ui\Component\Listing\Column;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Magento\Support\Model\Backup;
use Magento\Support\Model\Backup\Item\Code;
use Magento\Support\Model\Backup\Item\Db;
use Magento\Support\Model\Backup\Status;
use Magento\Support\Ui\Component\Listing\Column\DbDump;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbDumpTest extends TestCase
{
    /**
     * @var DbDump
     */
    protected $dbDump;

    /**
     * @var \Magento\Support\Model\BackupFactory|MockObject
     */
    protected $backupFactoryMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupMock;

    /**
     * @var Status|MockObject
     */
    protected $statusMock;

    /**
     * @var Code|MockObject
     */
    protected $itemCodeMock;

    /**
     * @var Db|MockObject
     */
    protected $itemDbMock;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->backupMock = $this->createMock(Backup::class);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->backupFactoryMock->expects($this->once())->method('create')->willReturn($this->backupMock);

        $this->statusMock = $this->getMockBuilder(Status::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemCodeMock = $this->getMockBuilder(Code::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->itemDbMock = $this->getMockBuilder(Db::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(ContextInterface::class)
            ->getMockForAbstractClass();
        $processor = $this->getMockBuilder(Processor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->expects($this->never())->method('getProcessor')->willReturn($processor);

        $this->dbDump = $this->objectManager->getObject(
            DbDump::class,
            [
                'status' => $this->statusMock,
                'backupFactory' => $this->backupFactoryMock,
                'context' => $contextMock,
            ]
        );
    }

    public function testPrepareDataSourceWithNameAsString()
    {
        $link = 'http://magento2.loc/admin/support/backup/download/backup_id/1/type/2/';
        $dbDumbLabel = 'd7fbf8df3c6e65b2dee080788281f83f.sql.gz';
        $dbDumpSize = '98.5 KiB';
        $log = 'Db dump was created successfully.';
        $lastUpdate = '2015-08-19 14:54:42';
        $name = 'd7fbf8df3c6e65b2dee080788281f83f';

        $dataSource = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => $name,
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null
                    ]
                ]
            ]
        ];

        $expectedResult = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => $name,
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null,
                        'db_name' => [
                            'label' => $dbDumbLabel,
                            'value' => [
                                'isLink' => 1,
                                'link' => $link
                            ],
                            'size' => $dbDumpSize
                        ],
                    ]
                ]
            ]
        ];

        $items = [
            'code' => $this->itemCodeMock,
            'db' => $this->itemDbMock
        ];

        $this->dbDump->setData(['name' => 'db_name']);

        $this->backupMock->expects($this->atLeastOnce())->method('setData');
        $this->backupMock->expects($this->once())->method('getItems')->willReturn($items);

        $this->statusMock->expects($this->once())->method('getDbDumpLabel')->willReturn($dbDumbLabel);
        $this->statusMock->expects($this->once())->method('getValue')
            ->willReturn(
                [
                    'isLink' => 1,
                    'link' => $link
                ]
            );
        $this->statusMock->expects($this->once())->method('getSize')->willReturn($dbDumpSize);

        $this->assertEquals($expectedResult, $this->dbDump->prepareDataSource($dataSource));
    }

    public function testPrepareDataSourceWithNameAsArray()
    {
        $dbDumpLink = 'http://magento2.loc/admin/support/backup/download/backup_id/1/type/2/';
        $codeDumpLink = 'http://magento2.loc/admin/support/backup/download/backup_id/1/type/1/';
        $dbDumbLabel = 'd7fbf8df3c6e65b2dee080788281f83f.sql.gz';
        $codeDumpLabel = 'd7fbf8df3c6e65b2dee080788281f83f.tar.gz';
        $dbDumpSize = '98.5 KiB';
        $codeDumpSize = '35.8 MiB';
        $log = 'Db dump was created successfully.';
        $lastUpdate = '2015-08-19 14:54:42';

        $dataSource = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => [
                            'label' => $codeDumpLabel,
                            'value' => [
                                'isLink' => 1,
                                'link' => $codeDumpLink
                            ],
                            'size' => $codeDumpSize
                        ],
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null
                    ]
                ]
            ]
        ];

        $expectedResult = [
            'data' => [
                'totalRecords' => 1,
                'items' => [
                    [
                        'id_field_name' => 'backup_id',
                        'backup_id' => '1',
                        'name' => [
                            'label' => $codeDumpLabel,
                            'value' => [
                                'isLink' => 1,
                                'link' => $codeDumpLink
                            ],
                            'size' => $codeDumpSize
                        ],
                        'status' => '1',
                        'last_update' => $lastUpdate,
                        'log' => $log,
                        'orig_data' => null,
                        'db_name' => [
                            'label' => $dbDumbLabel,
                            'value' => [
                                'isLink' => 1,
                                'link' => $dbDumpLink
                            ],
                            'size' => $dbDumpSize
                        ],
                    ]
                ]
            ]
        ];

        $items = [
            'code' => $this->itemCodeMock,
            'db' => $this->itemDbMock
        ];

        $this->dbDump->setData(['name' => 'db_name']);

        $this->backupMock->expects($this->atLeastOnce())->method('setData');
        $this->backupMock->expects($this->once())->method('getItems')->willReturn($items);

        $this->statusMock->expects($this->once())->method('getDbDumpLabel')->willReturn($dbDumbLabel);
        $this->statusMock->expects($this->once())->method('getValue')
            ->willReturn(
                [
                    'isLink' => 1,
                    'link' => $dbDumpLink
                ]
            );
        $this->statusMock->expects($this->once())->method('getSize')->willReturn($dbDumpSize);

        $this->assertEquals($expectedResult, $this->dbDump->prepareDataSource($dataSource));
    }
}
