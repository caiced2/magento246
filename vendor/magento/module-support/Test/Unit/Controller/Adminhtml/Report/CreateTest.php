<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Support\Test\Unit\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Support\Controller\Adminhtml\Report\Create;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var ManagerInterface|MockObject
     */
    protected $messageManagerMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var Title|MockObject
     */
    protected $titleMock;

    /**
     * @var Create
     */
    protected $createAction;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->messageManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->resultPageMock = $this->createMock(Page::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);
        $this->configMock = $this->createMock(Config::class);
        $this->titleMock = $this->createMock(Title::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->createAction = $this->objectManagerHelper->getObject(
            Create::class,
            [
                'context' => $this->contextMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $message = 'After you make your selections, click the "Create" button.'
            . ' Then stand by while the System Report is generated. This may take a few minutes.'
            . ' You will receive a notification once this step is completed.';
        $this->messageManagerMock->expects($this->once())
            ->method('addWarning')
            ->with(__($message))
            ->willReturnSelf();

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($this->resultPageMock);

        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_report')
            ->willReturnSelf();

        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configMock);
        $this->configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($this->titleMock);
        $this->titleMock->expects($this->once())
            ->method('prepend')
            ->with(__('Create System Report'));

        $this->assertSame($this->resultPageMock, $this->createAction->execute());
    }
}
