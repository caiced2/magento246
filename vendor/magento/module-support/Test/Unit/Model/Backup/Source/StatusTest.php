<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Model\Backup\Source;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\Backup;
use Magento\Support\Model\Backup\Source\Status;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Status
     */
    protected $status;

    /**
     * @var Backup|MockObject
     */
    protected $backupMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->backupMock = $this->createMock(Backup::class);

        $this->status = $this->objectManagerHelper->getObject(
            Status::class,
            ['backup' => $this->backupMock]
        );
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        $expectedResult = [
            ['label' => '', 'value' => ''],
            ['label' => 'titleStatusOne', 'value' => 'statusOne'],
            ['label' => 'titleStatusTwo', 'value' => 'statusTwo']
        ];

        $this->backupMock->expects($this->once())
            ->method('getAvailableStatuses')
            ->willReturn([
                'statusOne' => 'titleStatusOne',
                'statusTwo' => 'titleStatusTwo'
            ]);

        $this->assertSame($expectedResult, $this->status->toOptionArray());
    }
}
