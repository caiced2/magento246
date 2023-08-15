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
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Support\Controller\Adminhtml\Report\Index;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var Title|MockObject
     */
    protected $titleMock;

    /**
     * @var Config|MockObject
     */
    protected $configMock;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var ResultFactory|MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Context|MockObject
     */
    protected $contextMock;

    /**
     * @var Index
     */
    protected $indexAction;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->titleMock = $this->createMock(Title::class);
        $this->configMock = $this->createMock(Config::class);
        $this->resultPageMock = $this->createMock(Page::class);
        $this->resultFactoryMock = $this->createMock(ResultFactory::class);

        $this->contextMock = $this->createMock(Context::class);
        $this->contextMock->expects($this->any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);

        $this->indexAction = $this->objectManagerHelper->getObject(
            Index::class,
            ['context' => $this->contextMock]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $this->titleMock->expects($this->once())
            ->method('prepend')
            ->with(__('System Reports'));

        $this->configMock->expects($this->once())
            ->method('getTitle')
            ->willReturn($this->titleMock);

        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Support::support_report')
            ->willReturnSelf();
        $this->resultPageMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configMock);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_PAGE)
            ->willReturn($this->resultPageMock);

        $this->assertSame($this->resultPageMock, $this->indexAction->execute());
    }
}
