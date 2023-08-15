<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Block\Adminhtml\Backup;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Block\Adminhtml\Backup\Log;
use Magento\Support\Model\Backup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\BackupFactory|MockObject ;
     */
    protected $backupFactoryMock;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var Backup|MockObject
     */
    protected $backupMock;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \Magento\Support\Block\Adminhtml\Backup\Log
     */
    protected $logBlock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->backupFactoryMock = $this->createPartialMock(\Magento\Support\Model\BackupFactory::class, ['create']);
        $this->requestMock = $this->getMockForAbstractClass(RequestInterface::class);
        $this->backupMock = $this->createMock(Backup::class);

        $this->context = $this->objectManagerHelper->getObject(
            Context::class,
            [
                'request' => $this->requestMock
            ]
        );
        $this->logBlock = $this->objectManagerHelper->getObject(
            Log::class,
            [
                'context' => $this->context,
                'backupFactory' => $this->backupFactoryMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetHeaderText()
    {
        $headerText = __('Backup Log Details');
        $this->assertEquals($headerText, $this->logBlock->getHeaderText());
    }

    /**
     * @return void
     */
    public function testGetBackup()
    {
        $id = 1;
        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id', 0)
            ->willReturn($id);

        $this->backupMock->expects($this->once())
            ->method('load')
            ->with($id)
            ->willReturnSelf();

        $this->backupFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->backupMock);

        $this->assertSame($this->backupMock, $this->logBlock->getBackup());
    }

    /**
     * @return void
     */
    public function testGetBackupWithSetBackup()
    {
        $this->requestMock->expects($this->never())->method('getParam');
        $this->backupMock->expects($this->never())->method('load');
        $this->backupFactoryMock->expects($this->never())->method('create');

        $this->logBlock->setBackup($this->backupMock);
        $this->assertSame($this->backupMock, $this->logBlock->getBackup());
    }
}
