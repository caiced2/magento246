<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Backup;

use Magento\Backend\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup\AbstractItem;
use Magento\Support\Model\Backup\Status;
use Magento\Support\Model\DataFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Backup\Status
     */
    protected $status;

    /**
     * @var Data|MockObject
     */
    protected $dataHelperMock;

    /**
     * @var DataFormatter|MockObject
     */
    protected $dataFormatterMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->dataFormatterMock = $this->createMock(DataFormatter::class);
        $this->dataHelperMock = $this->createMock(Data::class);

        $this->status = $this->objectManagerHelper->getObject(
            Status::class,
            [
                'dataHelper' => $this->dataHelperMock,
                'dataFormatter' => $this->dataFormatterMock
            ]
        );
    }

    /**
     * @param int $status
     * @param array $expectedResult
     * @return void
     * @dataProvider getValueDataProvider
     */
    public function testGetValue($status, $expectedResult)
    {
        $backupId = 1;
        $type = 1;
        $params = ['backup_id' => $backupId, 'type' => $type];

        $item = $this->getAbstractItem();
        $item->expects($this->once())
            ->method('getBackupId')
            ->willReturn($backupId);
        $item->expects($this->once())
            ->method('getType')
            ->willReturn($type);
        $item->expects($this->any())
            ->method('getStatus')
            ->willReturn($status);

        $this->dataHelperMock->expects($this->once())
            ->method('getUrl')
            ->with('support/backup/download', $params)
            ->willReturn('http://localhost/some_link');

        $this->assertEquals($expectedResult, $this->status->getValue($item));
    }

    /**
     * @return array
     */
    public function getValueDataProvider()
    {
        return [
            [
                'status' => AbstractItem::STATUS_PROCESSING,
                'expectedResult' => ['isLink' => 0, 'link' => __('Processing ...')]
            ],
            [
                'status' => AbstractItem::STATUS_COMPLETE,
                'expectedResult' => ['isLink' => 1, 'link' => 'http://localhost/some_link']
            ],
            [
                'status' => -1,
                'expectedResult' => ['isLink' => 0, 'link' => __('Unknown Status')]
            ],
        ];
    }

    /**
     * @return void
     */
    public function testGetCodeDumpLabel()
    {
        $name = 'code name';
        $item = $this->getAbstractItem();
        $item->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertSame($name, $this->status->getCodeDumpLabel($item));
    }

    /**
     * @return void
     */
    public function testGetDbDumpLabel()
    {
        $name = 'db name';
        $item = $this->getAbstractItem();
        $item->expects($this->once())
            ->method('getDbName')
            ->willReturn($name);

        $this->assertSame($name, $this->status->getDbDumpLabel($item));
    }

    /**
     * @return void
     */
    public function testGetSize()
    {
        $size = 10;
        $formattedSize = '10Mb';

        $item = $this->getAbstractItem();
        $item->expects($this->once())
            ->method('getSize')
            ->willReturn($size);
        $this->dataFormatterMock->expects($this->once())
            ->method('formatBytes')
            ->with($size)
            ->willReturn($formattedSize);

        $this->assertSame($formattedSize, $this->status->getSize($item));
    }

    /**
     * @return \Magento\Support\Model\Backup\AbstractItem|MockObject
     */
    protected function getAbstractItem()
    {
        $item = $this->getMockBuilder(AbstractItem::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStatus', 'getBackupId', 'getType', 'getName', 'getDbName', 'getSize'])
            ->getMockForAbstractClass();

        return $item;
    }
}
